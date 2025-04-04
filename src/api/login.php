<?php
header("Content-Type:application/json");
require_once ('../util/api_authentication.php');
require_once ('../util/database_operations.php');
require_once ('../util/api_helper_functions.php');
require_once ('../util/uuid_adapter.php');
$DB = getDatabaseHandleOrDie();
authorizeOrDie();

//token = organizationMemberToken
if (isset($_GET["token"])) {
    $uuid = htmlspecialchars($_GET["token"]);
    $uuid = getUuid($uuid);
    isValidUuidOrDie($uuid);
    if (!userExists($uuid)) {
        http_response_code(401);

        echo json_encode(apiErrorMessage("UNAUTHORIZED", "Unauthorized",
            "You are not allowed to use this service."));
        die();
    }

    $userData = getUserData($uuid);

    if ($userData === null) {
        http_response_code(403);

        echo json_encode(apiErrorMessage("NO_USERDATA",
            "No user data", "No user data received."));
        die("Could not fetch userdata");
    }
    
    if (array_key_exists("startMap", $userData)) {
        $map = $userData["startMap"];
    } else {
        $map = getenv('START_ROOM_URL');
    }

    $result['userUuid'] = $uuid;
    $result['email'] = $userData["email"];
    $result['roomUrl'] = $map;
    $result['mapUrlStart'] = getMapFileUrl($map);
    // optional parameters
    $result['messages'] = array(); // messages are being sent when calling the access function

    echo json_encode($result);
} else {
    http_response_code(404);

    echo json_encode(apiErrorMessage("INSUFFICIENT_USER_INFORMATION",
        "Insufficient user information", "You did not specify enough information about the user."));
}
$DB = NULL;
?>
