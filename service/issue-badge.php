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
* Implements the web-service endpoint that issues all badges.
*
* The web request must supply the following arguments...
*  email : Email address of the user that earns the badge
*  badge : The identifier of the badge to be issued
*  proof : Proof that the specific user has earned the badge
*
* For example, the web-request will look something like the following:
*
*   http://example.com/badge-issuer-example/service/issue-badge.php?email=someone@example.com&badge=XXX&proof=YYY
*
* The web-service returns a JSON-encoded result.  If the badge is successfully
* issued, the result will contain the URL of the newly issued badge's
* assertion data.  The assertion data in turn is embedded into a badge image.
* A successful result would for example look something like the following:
*  {"assertion":"http:\/\/example.com\/badge-issuer-example\/data\/assertions\/bbdc14e0d2fc244bec86733b9ea3a32e.json"}
* If the badge cannot be issued, the returned JSON data will not contain an
* assertion URL; instead, it may include a debug message describing the
* problem when the service is configured to run in debug mode.
*/

/// @cond DOXYGEN_IGNORE_REGION

// Define the root path for this web application
define('APP_ROOT', dirname(getcwd()));

// Include related files
require_once APP_ROOT . '/includes/BadgeData.inc.php';

// Initialize global variables
$debugMode = TRUE;
$debugText = '';
$issuedUnixTime = time();
$assertionURL = NULL;
$success = TRUE;

/*
* Retrieves a named input argument provided by the web request.
*
* @param $name
*   The name of the value to be retrieved.
*
* @return
*   The value corresponding with the named argument.
*/
function GetInputArgument($name)
{
    if (isset($_REQUEST[$name]))
    {
        return (string)$_REQUEST[$name];
    }

    return NULL;
}

// Extract the data of the badge to be issued
$email = GetInputArgument('email');
$badgeID = GetInputArgument('badge');
$proof = GetInputArgument('proof');

// Perform data validation
if (!$email)
{
    $success = FALSE;
    $debugText = 'Missing email!';
}

if ($success
    && (!$badgeID))
{
    $success = FALSE;
    $debugText = 'Missing badge ID!';
}

if ($success)
{
    $success = BadgeData::IsBadgeAvailable($badgeID);
    if (!$success)
    {
        $debugText = 'Bad badge ID!';
    }
}

if ($success
    && (!BadgeData::IsValidProof($proof, $email, $badgeID)))
{
    $success = FALSE;
    $debugText = 'Invalid proof!';
}

// Determine the ID of the user that is associated with the current request
$userID = '';
if ($success)
{
    $userID = BadgeData::ConvertEmailToUserID($email);
    if (!$userID)
    {
        $success = FALSE;
        $debugText = 'Bad user ID!';
    }
}

// Create a new ID to be used for the assertion data (if the badge does eventually get issued)
$issuedID = '';
if ($success)
{
    $issuedID = BadgeData::CreateBadgeIssuedID($badgeID, $userID);
    if (!$issuedID)
    {
        $success = FALSE;
        $debugText = 'Bad issued ID!';
    }
}

// Create an instance of the badge data to be issued
$badgeData = NULL;
if ($success)
{
    $evidenceURL = NULL;  // Optional; specific to present badge being issued
    $badgeData = BadgeData::Create(
        $badgeID, $issuedID, $userID, $issuedUnixTime, $evidenceURL);
    if (!$badgeData)
    {
        $success = FALSE;
        $debugText = 'Cannot create badge data!';
    }
}

// Issue a badge by creating and persisting its assertion-JSON data
if ($success)
{
    $includeQualifiedURLs = TRUE;
    $assertionJSON = $badgeData->CreateAssertionJSON($includeQualifiedURLs);
    if (!$assertionJSON)
    {
        $success = FALSE;
        $debugText = 'Cannot create badge assertion!';
    }
    else
    {
        $assertionURL = $badgeData->SaveAssertionJSON($includeQualifiedURLs);
        if (!$assertionURL)
        {
            $success = FALSE;
            $debugText = 'Cannot save issued-badge data!';
        }
    }
}

// Create the resulting response-data of the request
if ($success
    && $assertionURL)
{
    $resultJSON = json_encode(array('assertion' => (string)$assertionURL));
}
elseif ($debugMode
    && $debugText)
{
    $resultJSON = json_encode(array('debug' => (string)$debugText));
}
else
{
    $resultJSON = '{}';
}

// Write the JSON data as the request output to the web client
header('Content-type: application/json');
echo $resultJSON;

/// @endcond  // DOXYGEN_IGNORE_REGION
