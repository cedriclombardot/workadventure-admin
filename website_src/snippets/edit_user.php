<?php
session_start();
?>
<!doctype html>
<html lang="en">

<body>
  <?php
require_once ('../util/database_operations.php');
require_once ('../util/web_login_functions.php');
require_once ('../util/uuid_adapter.php');
// Connect to database
$DB = getDatabaseHandleOrPrintError();
if (!isLoggedIn()) {
    $DB = NULL;
    die();
}
// Get user's uuid
if (!isset($_POST["uuid"])) {
?>
    <aside class="alert alert-danger" role="alert">
      <p>User not specified</p>
    </aside>
    <?php
    die();
}
$uuid = htmlspecialchars($_POST["uuid"]);

if(!isValidUuid($uuid)) {
?>
  <aside class="alert alert-danger" role="alert">
    The given user ID is invalid
  </aside>
<?php
  die();
}

createAccountIfNotExistent($uuid);
// Get new tag
if ((isset($_POST["action"])) && (htmlspecialchars($_POST["action"]) == "addTag") && (isset($_POST["tag"]))) {
    $tag = htmlspecialchars($_POST["tag"]);
    if (strlen(trim($tag)) > 0) {
        $addTagResult = addTag($uuid, $tag);
        if ($addTagResult == true) {
?>
        <aside class="alert alert-success" role="alert">
          The tag <?php echo "\"" . $tag . "\""; ?> has been added.
        </aside>
      <?php
        } else {
?>
        <aside class="alert alert-danger" role="alert">
          Could not add the tag <?php echo "\"" . $tag . "\""; ?>
        </aside>
      <?php
        }
    } else { ?>
      <aside class="alert alert-danger" role="alert">
        Can not store empty tags
      </aside>
      <?php
    }
}
// Get tag to remove
if ((isset($_POST["action"])) && (htmlspecialchars($_POST["action"]) == "removeTag") && (isset($_POST["tag"]))) {
    $tag = htmlspecialchars($_POST["tag"]);
    if (strlen(trim($tag)) > 0) {
        $remTagResult = removeTag($uuid, $tag);
        if ($remTagResult == true) {
?>
        <aside class="alert alert-success" role="alert">
          The tag <?php echo "\"" . $tag . "\""; ?> has been removed.
        </aside>
      <?php
        } else {
?>
        <aside class="alert alert-danger" role="alert">
          Could not remove the tag <?php echo "\"" . $tag . "\""; ?>.
        </aside>
      <?php
        }
    } else { ?>
      <aside class="alert alert-danger" role="alert">
        Can not remove empty tags
      </aside>
    <?php
    }
}
// Ban user if requested
if (isset($_POST["action"]) && (htmlspecialchars($_POST["action"]) == "ban") && (isset($_POST["reason"]))) {
    $reason = htmlspecialchars($_POST["reason"]);
    if (strlen(trim($reason)) > 0) {
      if (banUser($uuid, htmlspecialchars($_POST["reason"]))) {
?>
        <aside class="alert alert-success" role="alert">
          This user has been banned.
        </aside>
      <?php
      } else {
?>
        <aside class="alert alert-danger" role="alert">
          Could not ban this user.
        </aside>
      <?php
      }
    } else {
?>
      <aside class="alert alert-danger" role="alert">
        Did not ban user: no reason specified
      </aside>
    <?php
    }
}
//unban user if requested
if (isset($_POST["action"]) && (htmlspecialchars($_POST["action"]) == "unban")) {
    if (liftBan($uuid)) {
?>
      <aside class="alert alert-success" role="alert">
        This user's ban has been lifted.
      </aside>
    <?php
    } else {
?>
      <aside class="alert alert-danger" role="alert">
        Could not lift this user's ban.
      </aside>
    <?php
    }
}
// Update userdata if requested
if ((isset($_POST["action"])) && (htmlspecialchars($_POST["action"]) == "updateUserData") && (isset($_POST["name"])) && (isset($_POST["email"]))) {
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    if ((strlen(trim($name)) == 0) || (strlen(trim($email)) == 0)) {
?>
      <aside class="alert alert-danger" role="alert">
        The entered data is invalid
      </aside>
      <?php
    } else {
        $visitCardUrl = NULL;
        if (isset($_POST["visitCardUrl"])) {
            $visitCardUrl = htmlspecialchars($_POST["visitCardUrl"]);
        }
        if (updateUserData($uuid, $name, $email, $visitCardUrl)) { ?>
        <aside class="alert alert-success" role="alert">
          User data has been updated.
        </aside>
      <?php
        } else { ?>
        <aside class="alert alert-danger" role="alert">
          User data could not be updated.
        </aside>
      <?php
        }
    }
}
// remove message if requested
if ((isset($_POST["action"])) && (htmlspecialchars($_POST["action"]) == "removeMessage") && (isset($_POST["messageId"]))) {
    $id = htmlspecialchars($_POST["messageId"]);
    if (removeUserMessage($id)) { ?>
      <aside class="alert alert-success" role="alert">
        <p>Removed message</p>
      </aside>
    <?php
    } else { ?>
      <aside class="alert alert-danger" role="alert">
        <p>Could not remove message</p>
      </aside>
      <?php
    }
}
// send message if requested
if ((isset($_POST["action"])) && (htmlspecialchars($_POST["action"]) == "sendMessage") && (isset($_POST["message"]))) {
    $message = htmlspecialchars($_POST["message"]);
    if (strlen(trim($message)) > 0) {
        if (storeUserMessage($uuid, $message)) { ?>
        <aside class="alert alert-success" role="alert">
          <p>Stored user message</p>
        </aside>
      <?php
        } else { ?>
        <aside class="alert alert-danger" role="alert">
          <p>Could not store message</p>
        </aside>
      <?php
        }
    } else { ?>
      <aside class="alert alert-danger" role="alert">
        <p>User message must not be empty</p>
      </aside>
    <?php
    }
}
// get current user data
$userData = getUserData($uuid);
if ($userData == NULL) {
?>
    <aside class="alert alert-danger" role="alert">
      <p>Could not fetch user details</p>
    </aside>
  <?php
    die();
}
?>
  <main>
    <section>
      <form action="javascript:void(0);" style="margin-bottom: 1rem;">
        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input type="text" class="form-control" id="name" name="name" value="<?php echo $userData["name"]; ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" class="form-control" id="email" name="email" value="<?php echo $userData["email"]; ?>">
        </div>
        <label for="visitCardUrlLabel" class="form-label">Visit card URL</label>
        <div class="input-group mb-3">
          <span class="input-group-text" id="visitCardUrlPrefix">https://</span>
          <input type="text" class="form-control" id="visitCardUrlLabel" aria-describedby="visitCardUrlPrefix" name="visitCardUrl" value="<?php echo getUserVisitCardUrl($uuid) == NULL ? "" : getUserVisitCardUrl($uuid); ?>">
        </div>
        <button class="btn btn-primary" onclick="updateUserData('<?php echo $uuid; ?>');">Update</button>
      </form>
      <section>
        <section class="mb-3">
          <label for="name" class="form-label">Access Link</label>
          <input type="text" class="form-control" id="name" value="<?php echo "https://" . getenv('DOMAIN') . "/register/" . $userData["uuid"]; ?>" readonly>
        </section>
        <section class="mb-3">
          <?php
if (hasTags($uuid)) {
?>
            <p>Tags (click to remove):</p>
            <?php
    $tags = getTags($uuid);
    foreach ($tags as $currentTag) { ?>
              <button class="tag btn btn-primary" onclick="removeTag('<?php echo $uuid; ?>', '<?php echo $currentTag; ?>');">
                <?php echo $currentTag; ?>
              </button>
          <?
            }
            echo "<br><br>";
          }
          ?>
        </section>
        <section>
          <p>Add tag:</p>
          <form action="javascript:void(0)">
            <input class="form-control" type="text" id="newTag"><br>
            <button class="btn btn-primary" onclick="addTag('<?php echo $uuid; ?>');">Add tag</button>
          </form>
        </section>
        <section>
          <?php if (userMessagesExist($uuid)) {
            $messages = getUserMessages($uuid);
            if ($messages == NULL) { ?>
              <br>
              <div class="alert alert-danger" role="alert">
                <p>Could not fetch messages</p>
              </div>
            <?php
            } else { ?>
              <br>
              <div class="alert alert-warning" role="alert">
                <p>Only the top message will be shown to the user. If the user also receives a global message, the global
                  message will be shown instead of the private one!</p>
              </div>
              <p class="fs-3">User messages:</p>
              <table class="table">
                <tr>
                  <th scope="col">Message</th>
                  <th scope="col">Actions</th>
                </tr>
                <?php
                while ($row = $messages->fetch(PDO::FETCH_ASSOC)) { ?>
                  <tr>
                    <td>
                      <p class="fw-normal">
                        <?php echo $row["message"]; ?>
                      </p>
                    </td>
                    <td>
                      <button class="tag btn btn-danger" onclick="removeMessage('<?php echo $uuid; ?>', '<?php echo $row['message_id']; ?>');">
                        Remove
                      </button>
                    </td>
                  </tr>
                <?php
                } ?>
              </table> <?php
            }
        } ?>
        </section>
        <br>
        <section>
          <p>Send new user message:</p>
          <form action="javascript:void(0);">
            <div class="mb-3">
              <label for="messageInput" class="form-label">Message:</label>
              <textarea class="form-control" id="messageInput" name="message" rows="3"></textarea>
            </div>
            <button class="btn btn-primary" onclick="sendMessage('<?php echo $uuid; ?>');">
              Send
            </button>
          </form>
        </section>
        <section>
          <?php if (isBanned($uuid)) { ?>
            <br>
            <p>This user has been banned!</p>
            <div class="mb-3">
              <label for="banReason" class="form-label">Reason:</label>
              <input type="text" class="form-control" id="banReason" value="<?php echo getBanMessage($uuid); ?>" readonly>
            </div>
            <button class="btn btn-danger" onclick="unban('<?php echo $uuid; ?>');">Lift ban</button>
          <?php
        } else { ?>
            <br>
            <p>Ban this user:</p>
            <form action="javascript:void(0);">
              <div class="mb-3">
                <label for="banReason" class="form-label">Reason:</label>
                <input type="text" class="form-control" id="banReason" name="message">
              </div>
              <button class="btn btn-danger" onclick="ban('<?php echo $uuid; ?>');">Ban</button>
            </form>
          <?php
        } ?>
        </section>
        <br>
        <a class="btn btn-primary" href="../user" role="button">Go to users</a>
  </main>
</body>

</html>
<?php $DB = NULL; ?>