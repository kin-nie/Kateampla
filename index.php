<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kateampla Karaoke Reservation</title>
    <meta name="description"
        content="Book your karaoke room at Kateampla Karaoke and enjoy a fun-filled singing session with friends.">
    <meta name="keywords" content="Karaoke, Booking, Music, Fun, Singing">
    <link rel="stylesheet" href="style/home.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top navbar-bg-primary" data-bs-theme="dark"
        style="background-color: rgb(228, 119, 9)">
        <div class="container">
            <a class="navbar-brand" href="#">Kateampla Karaoke</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive"
                aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="login.php">Log-In</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Carousel -->
    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active"
                aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"
                aria-label="Slide 2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active" style="background-image: url('./assets/room2.jpeg');">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Book a Room Now!</h5>
                    <p>Experience a FUN Karaoke Session with our rooms!</p>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('./assets/room4.jpeg');">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Sing your heart out!</h5>
                    <p>Scream, shout, weep! It doesn't matter!</p>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
            data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
            data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- Rooms Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h2>Our KTV Rooms</h2>
                    <p>We have not ONE, but TWO exclusive KTV Rooms that will make you feel at home!</p>
                </div>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Break-In Room</h5>
                                    <p class="card-text">Good for 5-6 Singers. Experience a music-fied room that is
                                        complete from state-of-the-art sound systems and lighting up to catering to your
                                        bathroom needs!</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Break-Free Room</h5>
                                    <p class="card-text">Good for up to 12 Singers. Experience your KTV in a large hall
                                        while not worrying about the screen size as it is projected from a device!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>