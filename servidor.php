<?php
error_reporting(E_ALL);

/* Permitir al script esperar para conexiones. */
set_time_limit(0);

/* Activar el volcado de salida implícito, así veremos lo que estamo obteniendo
* mientras llega. */
ob_implicit_flush();

$address = '192.168.1.81';
$port = 10000;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() falló: razón: " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
}

//clients array
$clients = array();

do {
    $read = array();
    $read[] = $sock;
   
    $read = array_merge($read,$clients);
   
    // Set up a blocking call to socket_select
    //if(socket_select($read, $write = NULL, $except = NULL, $tv_sec = 5) < 1)
    $write=NULL;
    $except = NULL;
    if(socket_select($read, $write, $except, 0) < 1)	
    {
        //    SocketServer::debug("Problem blocking socket_select?");
        continue;
    }
   
    // Handle new Connections
    if (in_array($sock, $read)) {       
       
        if (($msgsock = socket_accept($sock)) === false) {
            echo "socket_accept() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
            break;
        }
        $clients[] = $msgsock;
        $key = array_keys($clients, $msgsock);
        /* Enviar instrucciones. */
        $msg = "\nBienvenido al Servidor De Prueba de PHP. \r\n" .
        "Usted es el cliente numero: {$key[0]}\r\n" .
        "Para salir, escriba 'quit'. Para cerrar el servidor escriba 'shutdown'.\r\n";
        socket_write($msgsock, $msg, strlen($msg));
       	
       	socket_getpeername($msgsock, $ip);
        echo "Nueva conexion al servidor: {$ip}\n";

        //$from = NULL;
        //$port = 0;
        //socket_recvfrom($sock, $buf, 12, 0, $from, $port);

        //echo "Se recibió $buf desde la dirección remota {$from} y el puerto remoto $port" . PHP_EOL;
    }
   
    // Handle Input
    foreach ($clients as $key => $client) { // for each client       
        if (in_array($client, $read)) {
            if (false === ($buf = socket_read($client, 2048, PHP_BINARY_READ))) {
                echo "socket_read() falló: razón: " . socket_strerror(socket_last_error($client)) . "\n";
                break 2;
            }
            if (!$buf = trim($buf)) {
                continue;
            }
            if ($buf == 'quit') {
                unset($clients[$key]);
                socket_close($client);
                break;
            }
            if ($buf == 'shutdown') {
                socket_close($client);
                break 2;
            }
            $talkback = "\r\nCliente {$key}: $buf\r\n";
            socket_write($client, $talkback, strlen($talkback));
            echo "Cliente {$key}: $buf\n";
        }
       
    }       
} while (true);

socket_close($sock);
?>
