<?php

$endpoint = 'http://w-adjuntos.scit62/app_dev.php/ws/AdjuntosApi';
$soapClientOptions['location'] = $endpoint;
$soapClientOptions['uri'] = $endpoint;
$username = '10a1a32fa4587742da32d9337264e5c0261041f7';
$password = '02bd9728e3ade6e7a27032984b75f6e1036fe60a';

// Instanciamiento del Cliente de Web Service con Seguridad WSSE Integrada

$client = new WSSESoapClient(
        null, $username, $password, $soapClientOptions
);
try {

    $plano = rand(1000, 10000);
    $anio = rand(2000, 2017);
    $regional = 'norte';

    // test 1 - adjuntar
    echo "<b>Adjuntar</b><br/>";
    $response = $client->adjuntar(base64_encode(file_get_contents("adjuntar.pdf")), "adjuntar.pdf", json_encode(array("id_precec" => 3100, "codigo_doc" => "PRECC")), '10.1.4.' . date("s"));
    if ('true' != $response->success) {
        echo "no se pudo adjuntar";
        var_dump($response);
        exit();
    }
    echo "Se pudo adjuntar. ID " . $response->data . "<br/>";
    $adjunto_id = $response->data;

    // test 2 - buscar por Id Inexistente;
    echo "<b>Buscar adjunto inexistente</b><br/>";
    $response = $client->adjuntoPorId($adjunto_id + 1);
    if ('true' == $response->success) {
        echo "devolvio true algo que no deberia exitir<br/>";
        var_dump($response);
        exit();
    }
    foreach ($response->errors as $e) {
        echo $e->message . "<br/>";
    }

    // test 2 - buscar por Id
    echo "<b>Buscar adjunto existente</b><br/>";
    echo "<a href='/test_ver_adjunto.php?adjunto_id=" . $adjunto_id . "' target='_blank'>" . $adjunto_id . "</a><br/>";

    // test 3 - buscar por claves inexistentes
    echo "<b>Buscar adjuntos por claves inexistente</b><br/>";
    $claves = array("plano" => $plano, "anio" => $anio + 1000, 'regional' => $regional);
    $claves = json_encode($claves);
    $response = $client->buscarAdjuntoIdPorClave($claves);
    if ('false' == $response->success || count($response->adjuntos) > 0) {
        echo "devolvio adjuntos algo que no deberia devolver";
        var_dump($response);
        exit();
    }
    echo "Adjuntos encontrados: " . count($response->adjuntos) . "<br/>";

    // test 4 - buscar por claves existentes
    echo "<b>Buscar adjuntos por claves existente</b><br/>";
    $claves = array("plano" => $plano, "anio" => $anio, 'regional' => $regional);
    $claves = json_encode($claves);
    $response = $client->buscarAdjuntoIdPorClave($claves);
    if ('true' != $response->success) {
        echo "devolvio false";
        var_dump($response);
        exit();
    }
    echo "Adjuntos encontrados: " . count($response->adjuntos) . "<br/>";
    echo "<pre>";
    foreach ($response->adjuntos as $a) {
        var_dump($a);
    }
    echo "</pre>";

    // test 4 -1 - buscar por claves existentes y no excluyente
    echo "<b>Buscar adjuntos por claves existente y no excluyente</b><br/>";
    $claves = array('regional' => $regional);
    $claves = json_encode($claves);
    $response = $client->buscarAdjuntosPorClave($claves);
    if ('true' != $response->success) {
        echo "devolvio false";
        var_dump($response);
        exit();
    }
    echo "Adjuntos encontrados: " . count($response->adjuntos) . "<br/>";
    echo "<pre>";
    foreach ($response->adjuntos as $a) {
        var_dump($a);
    }
    echo "</pre>";

    echo "<b>Borrado logico: " . ($adjunto_id - 1) . "</b><br/>";
    $response = $client->borrarLogico($adjunto_id - 1);
    if ('true' != $response->success) {
        echo "devolvio false";
        var_dump($response);
        exit();
    }
    echo "Se pudo borrar logico. ID " . $response->data . "<br/>";

    echo "<b>Borrado fisico</b><br/>";
    $response = $client->eliminarFisico($adjunto_id - 2);
    if ('true' != $response->success) {
        echo "devolvio false";
        var_dump($response);
        exit();
    }
    echo "Se pudo borrar logico. ID " . $response->data . "<br/>";


    echo "<b>Borrado logico: " . ($adjunto_id + 1) . "</b><br/>";
    $response = $client->borrarLogico($adjunto_id + 1);
    if ('true' == $response->success) {
        echo "devolvio true";
        var_dump($response);
        exit();
    }
    var_dump($response);

    echo "<b>Borrado fisico</b><br/>";
    $response = $client->eliminarFisico($adjunto_id + 1);
    if ('true' == $response->success) {
        echo "devolvio true";
        var_dump($response);
        exit();
    }
    var_dump($response);


    // test 2 - buscar por Id Inexistente;
    echo "<b>Buscar adjunto borrado</b><br/>";
    $response = $client->adjuntoPorId($adjunto_id - 1);
    if ('true' == $response->success) {
        echo "devolvio true algo que no deberia exitir<br/>";
        var_dump($response);
        exit();
    }
    foreach ($response->errors as $e) {
        echo $e->message . "<br/>";
    }

    // test 2 - buscar por Id Inexistente;
    echo "<b>Buscar adjunto eliminado</b><br/>";
    $response = $client->adjuntoPorId($adjunto_id - 2);
    if ('true' == $response->success) {
        echo "devolvio true algo que no deberia exitir<br/>";
        var_dump($response);
        exit();
    }
    foreach ($response->errors as $e) {
        echo $e->message . "<br/>";
    }
    ECHO "<h1>TEST EXITOSO</h1>";
    exit();






    /*

      if($servicioAInvocar=='adjuntar'){
      $response = $client->$servicioAInvocar(base64_encode(file_get_contents("adjuntar.pdf")), "adjuntar.pdf", json_encode(array("plano"=>"123456", "anio"=>"1982", 'regional' =>'norte')), '10.1.4.93');
      }elseif($servicioAInvocar=='adjuntoPorId'){
      $response = $client->$servicioAInvocar(72);
      }elseif($servicioAInvocar=='buscarAdjuntoIdPorClave'){
      $response = $client->$servicioAInvocar(json_encode(array("plano"=>"123456", "anio"=>"1982", 'regional' =>'norte')));
      var_dump($response);
      }

      // Invocacion del Servicio Web


      if ('true' == $response->success)
      {


      echo '<pre>';

      print_r($response);

      echo '</pre>';

      exit;
      }
      else
      {
      foreach($response->errors as $error)
      {
      print_r($error);
      }
      }

      if (!$produccion)
      {
      echo '<pre>';

      echo print_r($response);

      echo '</pre>';
      }
     * */
} catch (SoapFault $soapFault) {
    // TODO: Agregar logica de manejo de errores
    if (!$produccion) {
        var_dump($soapFault);

        echo "Request :<br>", htmlentities($client->__getLastRequest()), "<br>";
        echo "Response :<br>", htmlentities($client->__getLastResponse()), "<br>";
    }
} catch (Exception $exception) {
    // TODO: Agregar logica de manejo de errores

    if (!$produccion) {
        var_dump($exception);
    }
}

