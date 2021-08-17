<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <link href="css/quill.snow.css" rel="stylesheet">
  <script src="js/quill.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <title>Workadventure Administration</title>
  <script>
  // quill toolbar settings should match the settings of Workadventure
  // see front/src/Components/ConsoleGlobalMessageManager/InputTextGlobalMessage.svelte
  const quillToolbarSettings = [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{'header': 1}, {'header': 2}],
        [{'list': 'ordered'}, {'list': 'bullet'}],
        [{'script': 'sub'}, {'script': 'super'}],
        [{'indent': '-1'}, {'indent': '+1'}],
        [{'direction': 'rtl'}],
        [{'size': ['small', false, 'large', 'huge']}],
        [{'header': [1, 2, 3, 4, 5, 6, false]}],
        [{'color': []}, {'background': []}],
        [{'font': []}],
        [{'align': []}],
        ['clean'],
        ['link', 'image', 'video']
    ];
  </script>
</head>
<body>
  <?php
    // Connect to database
    try {
        $DB = new PDO("mysql:dbname=".getenv('DB_MYSQL_DATABASE').";host=admin-db;port=3306",
        getenv('DB_MYSQL_USER'), getenv('DB_MYSQL_PASSWORD'));
    }
    catch (PDOException $exception) {
        echo "<div class=\"container alert alert-danger\" role=\"alert\">";
        echo "Could not connect to database: ".$exception->getMessage();
        echo "</div>";
        return;
    }
    require_once 'api/database_operations.php';
    require_once 'login_functions.php';
    require 'meta/toolbar.php';

    if(!isLoggedIn()) {
        showLogin();
        die();
    }

    // remove message if requested
    if ((isset($_POST["removemessage"])) && (isset($_POST["message_id"]))) {
        $id = htmlspecialchars($_POST["message_id"]);
        if (deleteGlobalMessage($id)) { ?>
          <div class="container alert alert-success" role="alert">
            <p>Removed message</p>
          </div>
        <?php } else { ?>
            <div class="container alert alert-danger" role="alert">
              <p>Could not remove message</p>
            </div>
        <?php
        }
    }

    // create new message if requested
    if (isset($_POST["message"])) {
        $message = htmlspecialchars($_POST["message"]);
        if (createNewGlobalMessage($message)) { ?>
          <div class="container alert alert-success" role="alert">
            <p>Created new message</p>
          </div>
        <?php } else { ?>
            <div class="container alert alert-danger" role="alert">
              <p>Could not create new message</p>
            </div>
          <?php
        }
    }
    
    echo "<div class=\"container\">";

    // Get all messages
    $messages = getGlobalMessages();
    if ($messages == NULL) {
  ?>
    <div class="container alert alert-danger" role="alert">
      <p>Could not fetch global messages</p>
    </div>
  <?php die(); }
   if (globalMessagesExist()) {
    echo "<p class=\"fs-3\">Global messages:</p>";
  ?>
    <div class="container alert alert-warning" role="alert">
        <p>Only the top message will be shown to the user. If the user also receives a private message, the global message will be shown instead of the private one!</p>
    </div>
    <table class="table">
      <tr>
        <th scope="col">Message</th>
        <th scope="col">Actions</th>
      </tr>
    <?php
    $counter = 0;
    while($row = $messages->fetch(PDO::FETCH_ASSOC)) { ?>
      <tr><td>
      <?php
      echo "<div id=\"editor-container-".$counter."\" style=\"height: 150px;\"></div>"; ?>
      <script>
      var quill = new Quill('#editor-container-<?php echo $counter; ?>', {
        modules: {
            toolbar: false
        },
        theme: 'snow' });
      quill.setContents(<?php echo htmlspecialchars_decode($row["message"]); ?>.ops);
      quill.disable();
      </script>
      <?php
      echo "<td><form action=\"messages.php\" method=\"post\"><input class=\"tag btn btn-danger\" type=\"submit\" value=\"Remove\" name=\"removemessage\"><input type=\"hidden\" name=\"message_id\" value=\"".$row["message_id"]."\"></form></td></tr>";
      $counter++;
    }
    echo "</table>";
    }
  ?>
  <p class="fs-3">Create new global message:</p>
  <form action="messages.php" method="post" id="create-new-message">
    <div class="mb-3">
      <input type="hidden" name="message" id="message">
      <div id="editor-container" style="height: 150px;"></div>
      <script>
        var quill = new Quill('#editor-container', {
            modules: {
                toolbar: quillToolbarSettings
            },
            placeholder: 'Enter global message here...',
            theme: 'snow'
        });

        var form = document.getElementById("create-new-message");
        form.addEventListener("submit", quillSubmit);

        function quillSubmit() {
            const text = JSON.stringify(quill.getContents(0, quill.getLength()));
            document.getElementById('message').value = text;
        }
      </script>
    </div>
    <input type="submit" class="btn btn-primary" value="Create">
  </form>
  <?php $DB = NULL; ?>
</body>
</html>
