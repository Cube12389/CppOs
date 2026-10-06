<?php
// core/WebSocketServer.php

class WebSocketServer {
    private $address;
    private $port;
    private $master;
    private $sockets = [];
    private $users = []; // Map resourceId => User Data/Room

    // Callbacks
    public $onConnect;
    public $onMessage;
    public $onDisconnect;

    public function __construct($address = '0.0.0.0', $port = 8080) {
        $this->address = $address;
        $this->port = $port;
    }

    public function start() {
        // Create master socket
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->master === false) {
            die("socket_create() failed: " . socket_strerror(socket_last_error()) . "\n");
        }

        // Reuse address
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);

        // Bind
        if (socket_bind($this->master, $this->address, $this->port) === false) {
            die("socket_bind() failed: " . socket_strerror(socket_last_error($this->master)) . "\n");
        }

        // Listen
        if (socket_listen($this->master, 20) === false) {
            die("socket_listen() failed: " . socket_strerror(socket_last_error($this->master)) . "\n");
        }

        $this->sockets = [$this->master];
        echo "WebSocket Server started on {$this->address}:{$this->port}\n";

        // Main Loop
        while (true) {
            $read = $this->sockets;
            $write = null;
            $except = null;

            // Select
            if (socket_select($read, $write, $except, null) === false) {
                echo "socket_select() failed: " . socket_strerror(socket_last_error()) . "\n";
                continue;
            }

            foreach ($read as $socket) {
                if ($socket === $this->master) {
                    // Accept new connection
                    $client = socket_accept($this->master);
                    if ($client < 0) {
                        echo "socket_accept() failed\n";
                        continue;
                    }
                    $this->sockets[] = $client;
                    $this->performHandshake($client);
                } else {
                    // Read from existing connection
                    $bytes = @socket_recv($socket, $buffer, 2048, 0);
                    
                    if ($bytes === 0) {
                        // Disconnect
                        $this->disconnect($socket);
                    } else {
                        // Process Message
                        $payload = $this->decode($buffer);
                        if ($payload === false) {
                            // Close if decoding fails (protocol violation or close frame)
                            $this->disconnect($socket);
                        } else {
                            if ($this->onMessage) {
                                call_user_func($this->onMessage, $socket, $payload);
                            }
                        }
                    }
                }
            }
        }
    }

    private function performHandshake($client) {
        $headers = @socket_read($client, 2048);
        if ($headers === false) return;
        
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches)) {
            $key = trim($matches[1]);
            $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            
            $upgrade  = "HTTP/1.1 101 Switching Protocols\r\n";
            $upgrade .= "Upgrade: websocket\r\n";
            $upgrade .= "Connection: Upgrade\r\n";
            $upgrade .= "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
            
            socket_write($client, $upgrade);
            
            if ($this->onConnect) {
                call_user_func($this->onConnect, $client);
            }
        }
    }

    // Decode frame
    private function decode($payload) {
        if (!isset($payload[1])) return false; // Too short
        
        $length = ord($payload[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        } elseif ($length == 127) {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        } else {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6);
        }
        
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        
        return $text;
    }

    // Encode frame
    public function encode($text) {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        
        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
            
        return $header . $text;
    }

    public function disconnect($socket) {
        $index = array_search($socket, $this->sockets);
        if ($index >= 0) {
            array_splice($this->sockets, $index, 1);
            socket_close($socket);
            if ($this->onDisconnect) {
                call_user_func($this->onDisconnect, $socket);
            }
        }
    }
}
?>
