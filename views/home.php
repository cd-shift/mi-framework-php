<h1>Home</h1>

<h2>Hello, <?= $user ?> </h2>

<?php foreach (["Mensaje 1", "Mensaje 2"] as $message) { ?>
    <p><?= $message ?></p>
<?php } ?>