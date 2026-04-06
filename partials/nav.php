<?php
session_start();
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php?page=home">Kopikuni</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText"
            aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarText">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=home">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=posts">Posts</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=shopping">Shopping</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=about">About</a>
                </li>

            </ul>

            <span class="navbar-text">
                Kopikuni
            </span>
        </div>
    </div>
</nav>