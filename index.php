<?php
/*
* -----------------------------------------------------------------------
* BadgeBuddy - Badge Issuer Example
* Copyright (C) 2012  Project Whitecard Studios Inc.
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
* -----------------------------------------------------------------------
*/

/**
* @file
* Implements an example workflow that issues a badge in a web application.
*/

/// @cond DOXYGEN_IGNORE_REGION

// Define the root path for this web application
define('APP_ROOT', getcwd());

// Include related files
require_once APP_ROOT . '/includes/BadgeData.inc.php';

// Provide default values
$email = 'somebody@example.com';
$badgeID = '0817c9045c8f4861b478348719dbff91';
$proof = 'b9d5a32cc3b14ce08eb078eb43adc0be';

// Handle URL update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $email = (string)$_POST['email'];
    $badgeID = (string)$_POST['badge'];

    // Note: The "proof" logic must be more advanced for real websites
    switch ($badgeID)
    {
        case '0817c9045c8f4861b478348719dbff91':  // Example Badge (coded)
            $proof = 'b9d5a32cc3b14ce08eb078eb43adc0be';
            break;
        case 'e421610717e44f8a8352d19041ccb8a1':  // Template Badge (JSON)
            $proof = '128c1f12441b4228ac3a4a5ec6f2f82d';
            break;
        case 'ed74bafe781f4e6aa891f1fc0b2bcd1c':  // Template Badge (XML)
            $proof = '0a792c7539f64055913f7c1daaf7377b';
            break;
        default:
            $proof = '';
            break;
    }
}

// Build the URL to be used for issuing a badge
$badgeIssueURL = ('service/issue-badge.php' .
    '?email=' . urlencode($email) .
    '&badge=' . urlencode($badgeID) .
    '&proof=' . urlencode($proof));

// Produce the HTML page
header('Content-type: text/html');
?>
<html lang="en">
    <head>
        <title>Badge Issuing</title>
        <link type="text/css" rel="stylesheet" href="style.css" />
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="http://beta.openbadges.org/issuer.js"></script>
    </head>
    <body>
<h1>Example: Issue a Badge</h1>
<form method="post">
    <table>

    <tr>
        <td colspan="2">
            <hr />
            <p>This example illustrates how your web application can issue a badge to a user once the user has satisfied all the criteria for obtaining the badge.</p>
            <p>A badge is issued to a user as identified through an email address.</p>
            <p>The application must also supply some proof that the user is allowed to receive the specific badge.</p>
            <hr />
        </td>
    </tr>

    <tr>
        <td><label for="email">Email:</label></td>
        <td><input type="text" size="50" value="<?php echo htmlspecialchars($email); ?>" name="email" /></td>
    </tr>

    <tr>
        <td><label for="badge">Badge&nbsp;ID:</label></td>
        <td>
<?php
$badgeOptions = '';
foreach(BadgeData::GetCatalogBadgeNames() as $someBadgeID => $someBadgeName)
{
    $badgeOptions .= '<option value="' . htmlspecialchars($someBadgeID) . '"';
    if ($badgeID === $someBadgeID)
    {
        $badgeOptions .= ' selected="selected"';
    }

    $badgeOptions .= '>' .  htmlentities($someBadgeName) . '</option>' . "\r\n";
}

if (!$badgeOptions)
{
    echo '<input type="text" size="50" value="' . htmlspecialchars($badgeID) . '" name="badge" />';
}
else
{
    echo '<select name="badge">' . "\r\n";
    echo $badgeOptions;
    echo '</select>' . "\r\n";
}
?>
        </td>
    </tr>

    <tr>
        <td colspan="2"><input type="submit" value="Update Badge-Issue URL, incl. Proof" name="submit" /></td>
    </tr>

    <tr>
        <td colspan="2"><hr /></td>
    </tr>

    <tr>
        <td>Badge-Issue&nbsp;URL:</td>
        <td><a id="BadgeIssuerLink" href="<?php echo $badgeIssueURL; ?>">Create Badge via Issuer Web-Service</a></td>
    </tr>

    <tr>
        <td>&nbsp;</td>
        <td>
            <span id="BadgeIssuerResult">&nbsp;</span><br />
            <a id="BadgeIssuerResultLink" style="display: none;">View Assertion JSON</a><br />
            <a id="BadgeBakerLink" style="display: none;">Download Baked Badge</a><br />
            <a id="BadgeBackpackLink" style="display: none;" href=".">Store Badge in Backpack</a><br />
            <a id="BackpackViewLink" style="display: none;" href="http://beta.openbadges.org/">View your Mozilla Backpack</a><br />
            <img id="BadgeBakerImage" src="" style="display: none;" width="90" height="90" />
        </td>
    </tr>

    </table>
</form>
    <script>
        $(document).ready(function()
        {
            $('#BadgeIssuerResult').show();
            $('#BadgeIssuerResultLink').hide();
            $('#BadgeBakerLink').hide();
            $('#BadgeBakerImage').hide();
            $('#BadgeBackpackLink').hide();
            $('#BackpackViewLink').hide();
            $('#BadgeIssuerLink').click(function(event)
            {
                $('#BadgeIssuerResult').text('Loading...');
                $('#BadgeIssuerResultLink').hide();
                $('#BadgeBakerLink').hide();
                $('#BadgeBakerImage').hide();
                $('#BadgeBackpackLink').hide();
                $('#BackpackViewLink').hide();
                $.getJSON(event.target.href, function(data)
                {
                    if (data.assertion)
                    {
                        $('#BadgeIssuerResult').text(data.assertion);

                        $('#BadgeIssuerResultLink').attr('href', data.assertion);
                        $('#BadgeIssuerResultLink').show();

                        $('#BadgeBakerLink').attr('href', 'http://beta.openbadges.org/baker?assertion=' + data.assertion);
                        $('#BadgeBakerLink').show();

                        $('#BadgeBakerImage').attr('src', '');
                        $('#BadgeBakerImage').attr('src', 'http://beta.openbadges.org/baker?assertion=' + data.assertion);
                        $('#BadgeBakerImage').show();

                        $('#BadgeBackpackLink').show();
                        $('#BackpackViewLink').show();
                    }
                    else
                    {
                        $('#BadgeIssuerResult').text('No result');
                    }
                });

                event.preventDefault();
            });

            $('#BadgeBackpackLink').click(function(event)
            {
                OpenBadges.issue([ $('#BadgeIssuerResultLink').attr('href') ], function(errors, successes) { });
                event.preventDefault();
            });
        });
    </script>
    </body>
</html>
<?php
/// @endcond  // DOXYGEN_IGNORE_REGION
?>
