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
  <script src="js/bootstrap.min.js"></script>
  <title>Workadventure Administration</title>
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
    
    // Get user's uuid
    if (!isset($_POST["uuid"])) {
    ?>
        <div class="container alert alert-danger" role="alert">
          <p>User not specified</p>
        </div>
    <?php
        die();
    }

    $uuid = htmlspecialchars($_POST["uuid"]);
        
    // Get new tag
    if (isset($_POST["newtag"])) {
        $newTag = htmlspecialchars($_POST["newtag"]);
        if (!empty($newTag)) {
            $addTagResult = addTag($uuid, $newTag);
            if ($addTagResult == true) {
                ?>
                <div class="container alert alert-success" role="alert">
                  The tag <?php echo "\"".$newTag."\""; ?> has been added.
                </div>
              <?php
            }
            else
            {
                ?>
                <div class="container alert alert-danger" role="alert">
                  Could not add the tag <?php echo "\"".$newTag."\""; ?>
                </div>
                <?php
            }
        }
    }
    
    // Get tag to remove
    if (isset($_POST["remtag"])) {
        $remTag = htmlspecialchars($_POST["remtag"]);
        if (!empty($remTag)) {
            $remTagResult = removeTag($uuid, $remTag);
            if ($remTagResult == true) {
                ?>
                <div class="container alert alert-success" role="alert">
                    The tag <?php echo "\"".$remTag."\""; ?> has been removed.
                </div>
                <?php
            } else {
                ?>
                <div class="container alert alert-danger" role="alert">
                    Could not remove the tag <?php echo "\"".$remTag."\""; ?>.
                </div>
                <?php
            }
        }
    }

    // Ban user if requested
    if (isset($_POST["ban"])) {
      if ((isset($_POST["message"])) && (!empty($_POST["message"])) && (htmlspecialchars($_POST["ban"]) == "true")) {
        if (banUser($uuid, htmlspecialchars($_POST["message"]))) {
          ?>
            <div class="container alert alert-success" role="alert">
              This user has been banned.
            </div>
          <?php
        } else {
          ?>
            <div class="container alert alert-danger" role="alert">
              Could not ban this user.
            </div>
          <?php
        }
      } else if (htmlspecialchars($_POST["ban"]) == "false") {
        if (liftBan($uuid)) {
          ?>
            <div class="container alert alert-success" role="alert">
              This user's ban has been lifted.
            </div>
          <?php
        } else {
          ?>
            <div class="container alert alert-danger" role="alert">
              Could not lift this user's ban.
            </div>
          <?php
        }
      } else {
        ?>
            <div class="container alert alert-danger" role="alert">
              Ban message required.
            </div>
          <?php
      }
    }

    // Update userdata if requested
    if (isset($_POST["update-data"])) {
        if ((isset($_POST["name"])) && (isset($_POST["email"]))) {
            $name = htmlspecialchars($_POST["name"]);
            $email = htmlspecialchars($_POST["email"]);
            if ((empty($name)) || (empty($email))) {
            ?>
                <div class="container alert alert-danger" role="alert">
                New user data must not be empty.
                </div>
            <?php
            } else {
                if (updateUserData($uuid, $name, $email)) {
                ?>
                    <div class="container alert alert-success" role="alert">
                      User data has been updated.
                    </div>
                <?php
                } else {
                ?>
                    <div class="container alert alert-danger" role="alert">
                      User data could not be updated.
                    </div>
                <?php
                }
            }
        } else {
        ?>
            <div class="container alert alert-danger" role="alert">
              New user data could not be fetched.
            </div>
        <?php
        }
    }

    // get current user data
    $userData = getUserData($uuid);
    if ($userData == NULL) {
    ?>
        <div class="container alert alert-danger" role="alert">
          <p>Could not connect fetch user details</p>
        </div>
        <?php
        die();
    }
  ?>
<div class="container">
  <form action="edit_user.php" method="post" style="margin-bottom: 1rem;">
    <div class="mb-3">
      <label for="name" class="form-label">Name</label>
      <input type="text" class="form-control" id="name" name="name" value="<?php echo $userData["name"]; ?>">
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" class="form-control" id="email" name="email" value="<?php echo $userData["email"]; ?>">
    </div>
    <input type="hidden" name="uuid" value="<?php echo $uuid; ?>">
    <input class="btn btn-primary" type="submit" value="Update" name="update-data">
  </form>
  <div class="mb-3">
    <label for="name" class="form-label">Access Link</label>
    <input type="text" class="form-control" id="name" value="<?php echo "https://".getenv('DOMAIN')."/register/".$userData["uuid"]; ?>" readonly>
  </div>
  <div class="mb-3">
    <?php
     if (hasTags($uuid)) {
      ?>
      <p>Tags (click to remove):</p>
      <?php
        $tags = getTags($uuid);
        foreach ($tags as $currentTag) {
          echo "<form action=\"edit_user.php\" method=\"post\" class=\"sameline-form\"><input class=\"tag btn btn-primary\" type=\"submit\" value=\"".$currentTag."\" name=\"remtag\"><input type=\"hidden\" name=\"uuid\" value=\"".$uuid."\"></form>";
        }
        echo "<br><br>";
      }
    ?>
    <p>Add tag:</p>
    <form action="edit_user.php" method="post">
      <input class="form-control" type="text" name="newtag"><br>
      <input class="btn btn-primary" type="submit" value="Add tag">
      <input type="hidden" name="uuid" value="<?php echo $uuid; ?>">
    </form>
    <?php
        if (isBanned($uuid)) {
    ?>
      <br>
      <p>This user has been banned!</p>
      <div class="mb-3">
        <label for="ban_reason" class="form-label">Reason:</label>
        <input type="text" class="form-control" id="ban_reason" value="<?php echo getBanMessage($uuid); ?>" readonly>
      </div>
      <form action="edit_user.php" method="post">
        <input class="btn btn-danger" type="submit" value="Lift ban">
        <input type="hidden" name="uuid" value="<?php echo $uuid; ?>">
        <input type="hidden" name="ban" value="false">
      </form>
    <?php
        } else {
    ?>
      <br>
      <p>Ban this user:</p>
      <form action="edit_user.php" method="post">
        <div class="mb-3">
          <label for="ban_reason" class="form-label">Reason:</label>
          <input type="text" class="form-control" id="ban_reason" name="message">
        </div>
        <input class="btn btn-danger" type="submit" value="Ban">
        <input type="hidden" name="uuid" value="<?php echo $uuid; ?>">
        <input type="hidden" name="ban" value="true">
      </form>
    <?php
        }
    ?>
  </div>
  <input type="hidden" name="uuid" value="<?php echo $uuid; ?>">
  <a class="btn btn-primary" href="user.php" role="button">Go back</a> 
  <?php $DB = NULL; ?>
</div>
</body>
</html>
