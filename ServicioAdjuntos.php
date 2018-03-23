<?php

/**
 * Servicio para gestionar archivos adjuntos a traves del servicio web.
 * 
*/

namespace Scit\ServicioAdjunto;

use Exception;
use SoapFault;

/**
 * @author emiliano
 */
class ServicioAdjuntos {

    /**
     * Endpoint del servicio web de adjuntos.
     * 
     * @var string
     */
    private $endpoint;

    /**
     * Nombre de usuario utilizado para contectarse con el servicio web.
     * 
     * @var string
     */
    private $username;

    /**
     * Contraseña del usuario utilizado para contectarse con el servicio web.
     * 
     * @var string 
     */
    private $password;
    
    /**
     * Parametros pasados como opciones al servicio web.
     * 
     * @var array 
     */
    private $soapClientOptions;
    
    /**
     * Objeto utilizado para dialogar con el servicio web.
     * 
     * @var WSSESoapClient 
     */
    private $client;

    /**
     *
     * @var string
     */
    private $ultimoError;

    /**
     *
     * @var array
     */
    private $clavesBag;

    /**
     *
     * @var int
     */
    private $plano;

    /**
     *
     * @var int 
     */
    private $anio;

    /**
     *
     * @var string
     */
    private $ipOrigen;

    /**
     *
     * @var string
     */
    private $regional;

    public function __construct($endpoint, $username, $password) {
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;

        $this->soapClientOptions['location'] = $endpoint;
        $this->soapClientOptions['uri'] = $endpoint;

        $this->client = new WSSESoapClient(null, $this->username, $this->password, $this->soapClientOptions);
        $this->clavesBag = array();
        
        $this->ipOrigen = '10.1.4.' . date("s");

        $this->clavesBag = array();
    }

    /**
     * Permite guardar un archivo en el servidor.
     * 
     * Para el contenido se puede utilizar la funcion file_get_contents() pasandole 
     * como parametro el archivo.
     * Si el contenido no se pasa codificado se puede pasar el argumento $codificar como true.
     * 
     * @param string $contenido Contenido del archivo. 
     * @param string $nombre_de_archivo Nombre original del archivo
     * @param boolean $codificar  Si esta en true se le aplicara la funcion base64_encode()
     * @return boolean En caso de exito devuelve un numero entero correspondiente al id del adjunto, caso contrario devuelve false.
     */
    public function adjuntar($contenido, $nombre_de_archivo, $codificar = false) {

        $this->ultimoError = '';

        if (!$contenido || strlen($contenido) == 0) {
            $this->ultimoError = "El contenido debe ser un string de longitud mayor a cero.";
            return false;
        }

        if ($codificar == true) {
            $contenido = base64_encode($contenido);
        }

        if (count($this->clavesBag) === 0) {
            $this->ultimoError = 'No se definio ninguna clave.';
            return false;
        }
        
        try {
            $response = $this->client->adjuntar(
                    $contenido, $nombre_de_archivo, json_encode($this->clavesBag), $this->ipOrigen);

            if ('true' != $response->success) {
                $this->ultimoError = $response->errors;
                return false;
            }

            return intval($response->data);
        } catch (SoapFault $soapFault) {
            $this->printErrorEx();
        } catch (Exception $ex) {
            dump($ex);
            exit;
        }
    }

    /**
     * Busca un conjunto de adjuntos por las claves definidas.
     * 
     * @return mixed Arreglo con los items encontrados o false en caso contrario.
     */
    public function buscarPorClaves() {

        $this->ultimoError = '';

        try {

            $response = $this->client->buscarAdjuntoIdPorClave(
                    json_encode($this->clavesBag)
            );
            if ('true' != $response->success) {
                $this->ultimoError = $response->errors;
                return false;
            }

            if(!$response->adjuntos){
                return array();
            }

            $res = $response->adjuntos->item;            
            
            if($res instanceof \stdClass){
                // si devuelve un solo resultado lo devuelve como objeto y no como 
                // un arreglo con el item ...
                return array($res);
            }
            
            return $res;
            
        } catch (SoapFault $soapFault) {
            $this->printErrorEx();
        } catch (Exception $ex) {
            dump($ex);
            exit;
        }
    }   

    /**
     * Busca un adjunto por id.
     * 
     * @param int $id_adjunto
     * @return boolean
     */
    public function buscarPorId($id_adjunto) {

        $this->ultimoError = '';
        try {

            $response = $this->client->adjuntoPorId($id_adjunto);
            if ('true' != $response->success) {
                $this->ultimoError = $response->errors->item->message;
                return false;
            }
            
            return array("adjunto" => $response->adjunto,
                "contenido" => $response->contenido);

        } catch (SoapFault $soapFault) {
            $this->printErrorEx();
        } catch (Exception $ex) {
            dump($ex);
            exit;
        }
    }
    
    /**
     * Borra un archivo. El borrado puede ser logico(default) o fisico.
     * 
     * @param int $id_adjunto Id del adjunto
     * @param boolean $logico Si esta en true el borrado es logico, en caso contrario el borrado es fisico.
     * @return mixed El id del adjunto borrado o false en caso de error o adjunto inexistente.
     */
    public function borrar($id_adjunto, $logico = true) {
        
        $this->ultimoError = '';
        
        if(!is_numeric($id_adjunto)){
            $this->ultimoError = 'El id del adjunto debe ser un numero entero.';
            return false;
        }

        try {  
            $response = $logico ? 
                    $this->client->borrarLogico($id_adjunto) 
                    : $this->client->eliminarFisico($id_adjunto);
                        
            if ('true' != $response->success) {                
                $this->ultimoError = $response->errors;
                return false;                
            }

            return $response->data;
                        
        } catch (SoapFault $soapFault) {            
            $this->printErrorEx();
        } catch (Exception $ex) {
            dump($ex);
            exit;
        }
                
        return false;
    }
          

    // ==============================================================================================
    //Gestion de las claves

    /**
     * Crea una nueva clave. Solo la agrega si no existe.
     * Para agregar sin verificar la existencia usar setClave().
     * 
     * @param mixed $key
     * @param mixed $value
     * @return boolean true si se agrego, falso en caso contrario.
     */    
    function agregarClave($key, $value) {
        if (!$this->hasClave($key)) {
            $this->clavesBag[$key] = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Guarda o sobrescrive un par clave-valor.
     * 
     * @param mixed $key
     * @param mixed $value
     */
    function setClave($key, $value) {
        $this->clavesBag[$key] = $value;
    }

    /**
     * Borra una clave existente.
     * 
     * @param mixed $key
     * @return boolean
     */
    function removerClave($key) {
        if ($this->hasClave($key)) {
            unset($this->clavesBag[$key]);
            return true;
        }
        return false;
    }

    /**
     * Devuelve el valor asociado a la clave pasada como parametro.
     * 
     * @param mixed $key
     * @return mixed
     */
    function getClave($key) {
        if ($this->hasClave($key)) {
            return $this->clavesBag[$key];
        }
        return null;
    }

    /**
     * Devuelve todas las claves definidas
     * 
     * @return mixed
     */
    function getClaves() {
        return $this->clavesBag;
    }

    /**
     * Verifica si la clave $key ya se ha definido
     * 
     * @param mixed $key
     * @return boolean
     */
    function hasClave($key) {
        return isset($this->clavesBag[$key]);
    }

    /**
     * Vacia el contenedor de claves.
     * 
     */
    function resetClaves() {
        $this->clavesBag = array();
    }
    
    /**
     * Permite setear las claves desde un arreglo.
     * 
     * Resetea todas las claves seteadas previamente.     
     *
     * @param array $claves
     */
    function setClavesDesdeArray(array $claves){
        $this->resetClaves();
        foreach ($claves as $key => $value) {
            $this->agregarClave($key, $value);
        }        
    }

    //Getters & Setters

    /**
     * Devuelve el ultimo error.
     * 
     * @return type
     */
    function getUltimoError() {
        return $this->ultimoError;
    }

    function getIpOrigen() {
        return $this->ipOrigen;
    }

    function setIpOrigen($ipOrigen) {
        $this->ipOrigen = $ipOrigen;
    }    
   
    private function printErrorEx() {
        dump(htmlentities($this->client->__getLastRequest()));
        dump(htmlentities($this->client->__getLastResponse()));
        exit;
    }

}
