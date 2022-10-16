<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Socket.IO Test</title>
</head>

<body>
    <h1>Socket.IO Test</h1>
    <br />
    <br />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.0/socket.io.min.js"></script>
    <script>
        let host = @json(config('services.host_nodejs'));
        let port = @json(config('redis.port_socket'));
        let hostNodejs = port ? host + ':' + port + '' : host

        const socket = io(`${hostNodejs}`);

        socket.on('connect', () => {
            console.log('Connected to server');
        });

        socket.on('disconnect', () => {
            console.log('Disconnected from server');
        });
    </script>
</body>

</html>
