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
* Defines the base class that is used to contain the data that defines all
* badges that can be issued. The class is also used to persist an issued badge.
*/

// Include related files
require_once APP_ROOT . '/includes/BadgeDataExample.inc.php';
require_once APP_ROOT . '/includes/BadgeDataTemplate.inc.php';

/**
* Implements the base class that is used to interact with badge data.
*/
class BadgeData
{
    /**
    * Defines the hash code that is used to create user identifiers.
    */
    const HASH_USER_ID = 'projectwhitecard';

    /*
    * Caches the value exposed through the GetAppRootRelativeURL method.
    */
    private static $appRootRelativeURL = NULL;

    /**
    * Determines whether the provided proof is valid for issuing a specific badge.
    *
    * Note: It is recommended to modify this code to include authentication checks.
    *
    * @param $proof
    *   The proof provided by the client who claims she may get the specified badge.
    *
    * @param $userEmail
    *   The email address of the user who is claiming the badge, which may be used as part of validation.
    *
    * @param $badgeID
    *   The identifier of the badge to be issued.
    *
    * @return
    *   A boolean value indicating whether the proof is valid (TRUE) or invalid (FALSE).
    */
    public static function IsValidProof($proof, $userEmail = '', $badgeID = NULL)
    {
        switch ($badgeID)
        {
            case BadgeDataExample::BADGE_ID:
                return BadgeDataExample::IsValidProof($proof, $userEmail, $badgeID);
            default:
                if (BadgeDataTemplate::IsBadgeAvailable($badgeID))
                {
                    return BadgeDataTemplate::IsValidProof($proof, $userEmail, $badgeID);
                }

                break;
        }

        return FALSE;
    }

    /**
    * Determines whether the specified badge identifier is available for issuing.
    *
    * @param $badgeID
    *   The identifier of the badge that might be issued.
    *
    * @return
    *   A boolean value indicating whether the badge is available to be issued (TRUE) or unavailable (FALSE).
    */
    public static function IsBadgeAvailable($badgeID)
    {
        switch ($badgeID)
        {
            case BadgeDataExample::BADGE_ID:
                return TRUE;
            default:
                if (BadgeDataTemplate::IsBadgeAvailable($badgeID))
                {
                    return TRUE;
                }

                break;
        }

        return FALSE;
    }

    /**
    * Retrieves a collection of identifiers of all badges that may be available to be issued.
    *
    * @return
    *   Returns an array containing identifiers of all badges that may be available to be issued.
    */
    public static function GetCatalogBadgeIDs()
    {
        return array_merge(
            array(BadgeDataExample::BADGE_ID),
            BadgeDataTemplate::GetCatalogBadgeIDs());
    }

    /**
    * Retrieves a collection of all badges that may be available to be issued.
    *
    * @return
    *   Returns an array containing instances of type @ref BadgeData (or a derivative) that describes all badges that may be available to be issued.
    */
    public static function GetCatalogBadges()
    {
        $badges = array();
        array_push($badges, static::GetCatalogBadgeData(new BadgeDataExample()));
        return array_merge(
            $badges,
            BadgeDataTemplate::GetCatalogBadges());
    }

    /**
    * Extracts an associative array containing common data of a badge that may be available to be issued.
    *
    * @param $badge
    *   The badge object to extract the data from, specified as an instances of class-type @ref BadgeData (or a derivative).
    *
    * @return
    *   Returns an associative array containing common badge-data, with values for "badgeID", "badgeName", "description", etc.
    */
    protected static function GetCatalogBadgeData($badge)
    {
        $data = (array)$badge;
        $filter = array_flip(array(
            'badgeID',
            'badgeName',
            'description',
            'imageURL',
            'criteriaURL',
            'issuerOriginURL',
            'issuerName',
            'issuerOrganization',
            'issuerEmail',
            'expiresUnixTime'));
        return array_intersect_key($data, $filter);
    }

    /**
    * Retrieves a collection of names of all badges that may be available to be issued, including every badge identifier (as the keys of the array).
    *
    * @return
    *   Returns an associative array with a mapping of badge identifiers (as the array keys) to badge names (as the array values).
    */
    public static function GetCatalogBadgeNames()
    {
        return static::GetCatalogBadgeValues('badgeName');
    }

    /**
    * Retrieves a collection of values of all badges that may be available to be issued, including every badge identifier (as the keys of the array).
    *
    * @param $valueName
    *   The name of the values to be retrieved, such as "badgeName", "description" or "issuerEmail".
    *
    * @return
    *   Returns an associative array with a mapping of badge identifiers (as the array keys) to the requested values (as the array values).
    */
    protected static function GetCatalogBadgeValues($valueName)
    {
        $badgeValues = array();
        foreach (static::GetCatalogBadges() as $badge)
        {
            $badgeValues[$badge['badgeID']] = $badge[$valueName];
        }

        return $badgeValues;
    }

    /**
    * Creates an instance of the specified badge to be issued.
    *
    * Note: The badge's assertion data should be saved and made available at some URL before the badge can really be considered having been issued (refer to the @ref SaveAssertionJSON method).
    *
    * @param $badgeID
    *   The identifier of the badge to be issued, which can be thought of as the ID of a template on which the actual issued badge instance is based.
    *
    * @param $issuedID
    *   The optional identifier of the badge instance that is issued to a specific user, which will be auto-generated if not specified.
    *
    * @param $userID
    *   The identifier of the user to whom the badge is being issued (i.e., the recipient of the badge). This would typically be a salted hash of the user's email address.
    *
    * @param $issuedUnixTime
    *   An optional date when the badge was issued; the current time is used if none is provided.  This is an integer value representing a Unix timestamp, i.e., measured as the number of seconds since the Unix Epoch (January 1, 1970, 00:00:00 GMT).
    *
    * @param $evidenceURL
    *   The optional user-specific URL with information about this specific badge instance. The referenced content should contain information about how the specific user earned the badge.
    *
    * @return
    *   Returns an object instance of a @ref BadgeData or derived class that corresponds with the specified badge identifier.
    */
    public static function Create(
        $badgeID,
        $issuedID = NULL,
        $userID = NULL,
        $issuedUnixTime = 0,
        $evidenceURL = NULL)
    {
        switch ($badgeID)
        {
            case BadgeDataExample::BADGE_ID:
                return new BadgeDataExample(
                    $issuedID, $userID, $issuedUnixTime, $evidenceURL);
            default:
                if (BadgeDataTemplate::IsBadgeAvailable($badgeID))
                {
                    return new BadgeDataTemplate(
                        $badgeID, $issuedID, $userID, $issuedUnixTime, $evidenceURL);
                }

                break;
        }

        return NULL;
    }

    /**
    * Converts an email address to a hashed user-identifier.
    *
    * @param $userEmail
    *   The email address of the user that is claiming the badge.
    *
    * @param $hashAlgorithm
    *   An optional hashing algorithm to be used for creating the identifier, which defaults to SHA256.
    *
    * @return
    *   An identification string representing the user.
    */
    public static function ConvertEmailToUserID($userEmail, $hashAlgorithm = 'sha256')
    {
        if (!$hashAlgorithm)
        {
            $hashAlgorithm = 'sha256';
        }

        $userID = NULL;
        if ($userEmail)
        {
            $userEmail = strtolower(trim($userEmail));
            if ($userEmail)
            {
                $userID = $hashAlgorithm . '$' . hash($hashAlgorithm, $userEmail . self::HASH_USER_ID);
            }
        }

        return $userID;
    }

    /**
    * Generates an identifier for a new badge instance.
    *
    * @param $badgeID
    *   The identifier of the badge to be issued.
    *
    * @param $userID
    *   The identifier of the user that is claiming the badge.
    *
    * @return
    *   An identification string representing a badge instance to be issued.
    */
    public static function CreateBadgeIssuedID($badgeID, $userID = '')
    {
        $issuedID = NULL;
        if ($badgeID)
        {
            $issuedID = (string)$badgeID;
            if ($userID)
            {
                $issuedID .= '@' . $userID;
            }

            $issuedID = uniqid($issuedID, TRUE);
            $issuedID = hash('md5', $issuedID);
        }

        return $issuedID;
    }

    /**
    * Retrieves the URL of the current web request.
    *
    * @return
    *   A string that specifies the URL of the current page.
    */
    protected static function GetCurrentPageURL()
    {
        return static::GetCurrentDomainURL() . $_SERVER['REQUEST_URI'];
    }

    /**
    * Retrieves the domain-name portion of the URL for the current web request.
    *
    * @return
    *   A string that specifies the base URL of the current page.
    */
    protected static function GetCurrentDomainURL()
    {
        $url = 'http';
        if (isset($_SERVER['HTTPS'])
            && ($_SERVER['HTTPS'] == 'on'))
        {
           $url .= 's';
        }

        $url .= '://' . $_SERVER['SERVER_NAME'];
        if (isset($_SERVER['SERVER_PORT'])
            && ($_SERVER['SERVER_PORT'] != '80'))
        {
           $url .= ':' . $_SERVER['SERVER_PORT'];
        }

        return $url;
    }

    /**
    * Retrieves the path prefix to be included in the URL when converting from an application-root relative path to a fully-qualified URL.
    *
    * @return
    *   A string representing the application-root relative path needed for a qualified URL.
    */
    protected static function GetAppRootRelativeURL()
    {
        if (!isset(self::$appRootRelativeURL))
        {
            $requestURI = NULL;
            if (isset($_SERVER['PHP_SELF']))
            {
               $requestURI = $_SERVER['PHP_SELF'];
            }
            elseif (isset($_SERVER['REQUEST_URI']))
            {
               $requestURI = $_SERVER['REQUEST_URI'];
            }

            if ($requestURI)
            {
                self::$appRootRelativeURL = ('/' . implode('/', array_intersect(explode('/', trim($requestURI, '/')), explode('/', trim(str_replace('\\', '/', APP_ROOT), '/')))));
            }
        }

        return self::$appRootRelativeURL;
    }

    /**
    * Retrieves the qualified version of the specified relative URL.
    *
    * @param $relativeURL
    *   The original URL that must be qualified if it is not already qualified.
    *
    * @param $includeRootUrlPrefix
    *   An optional boolean value indicating whether to include the application-root relative path in the URL (TRUE) or leave it out (FALSE). The application-root relative path will also be included if the domain-name must be included.
    *
    * @param $includeDomainAlso
    *   An optional boolean value indicating whether to include the current domain name in the URL (TRUE) or leave it out (FALSE). The application-root relative path will also be included if the domain-name must be included.
    *
    * @return
    *   A string representing the qualified URL.
    */
    protected static function GetQualifiedURL(
        $relativeURL, $includeRootUrlPrefix = FALSE, $includeDomainAlso = FALSE)
    {
        if ($relativeURL === NULL)
        {
            return $relativeURL;  // Success: return relative URL
        }

        if ((strpos($relativeURL, 'http:') === 0)
            || (strpos($relativeURL, 'https:') === 0))
        {
            return $relativeURL;  // Already fully qualified
        }

        if ($includeDomainAlso)
        {
            // The root prefix must be included if the domain-name is included
            $includeRootUrlPrefix = TRUE;
        }

        if ((strpos($relativeURL, '/') === 0)
            || (!$includeRootUrlPrefix))
        {
            // Already partly qualified, or further qualification not requested
            $qualifiedURL = $relativeURL;  // Already partly qualified
        }
        else
        {
            $appRootURL = static::GetAppRootRelativeURL();  // e.g., '/path/to/app/root'
            $qualifiedURL = ((string)$appRootURL) . '/' . $relativeURL;
        }

        if ($includeDomainAlso)
        {
            if (strpos($qualifiedURL, '/') !== 0)
            {
                $qualifiedURL = '/' . $qualifiedURL;
            }

            $baseURL = static::GetCurrentDomainURL();  // e.g., 'http://example.com'
            $qualifiedURL = $baseURL . $qualifiedURL;
        }

        return $qualifiedURL;
    }

    /**
    * Stores the identifier of the badge to be issued, which can be thought of as the ID of a template on which the actual issued badge instance is based.
    */
    public $badgeID;

    /**
    * Stores the identifier of the badge instance that is issued to a specific user.
    */
    public $issuedID;

    /**
    * Stores the identifier of the user to whom the badge is being issued (i.e., the recipient of the badge). This would typically be a salted hash of the user's email address.
    */
    public $userID;

    /**
    * Stores the human-readable name of the badge being issued.
    */
    public $badgeName;

    /**
    * Stores a description of the badge being issued.
    */
    public $description;

    /**
    * Stores the URL of the original image representing the badge, before it is modified and issued to the user.
    */
    public $imageURL;

    /**
    * Stores the URL describing the badge and criteria for earning the badge in general (i.e., not for the specific instance of the badge).
    */
    public $criteriaURL;

    /**
    * Stores the origin of the issuer, which is a base URL containing the domain name of the issuer.
    */
    public $issuerOriginURL;

    /**
    * Stores the human-readable name of the issuing agent.
    */
    public $issuerName;

    /**
    * Stores the organization for which the badge is issued, which is optional.
    */
    public $issuerOrganization;

    /**
    * Stores the optional human-monitored email address associated with the issuer.
    */
    public $issuerEmail;

    /**
    * Stores the date when the badge was issued.  This is an integer value representing a Unix timestamp, i.e., measured as the number of seconds since the Unix Epoch (January 1, 1970, 00:00:00 GMT).
    */
    public $issuedUnixTime;

    /**
    * Stores the optional date when the badge expires. If zero, the badge never expires.  This is an integer value representing a Unix timestamp, i.e., measured as the number of seconds since the Unix Epoch (January 1, 1970, 00:00:00 GMT).
    */
    public $expiresUnixTime;

    /**
    * Stores the JSON-encoded assertion of the badge once it is issued.
    */
    public $assertionJSON;

    /**
    * Stores the optional user-specific URL with information about this specific badge instance. The referenced content should contain information about how the specific user earned the badge.
    */
    public $evidenceURL;

    /**
    * Creates an instance of a specific badge being issued.
    *
    * @param $badgeID
    *   The identifier of the badge to be issued, which can be thought of as the ID of a template on which the actual issued badge instance is based.
    *
    * @param $issuedID
    *   The optional identifier of the badge instance that is issued to a specific user, which will be auto-generated if not specified.
    *
    * @param $userID
    *   The identifier of the user to whom the badge is being issued (i.e., the recipient of the badge). This would typically be a salted hash of the user's email address.
    *
    * @param $badgeName
    *   The human-readable name of the badge being issued.
    *
    * @param $description
    *   The description of the badge being issued.
    *
    * @param $imageURL
    *   The URL of the original image representing the badge, before it is modified and issued to the user.
    *
    * @param $criteriaURL
    *   The URL describing the badge and criteria for earning the badge in general (i.e., not for the specific instance of the badge).
    *
    * @param $issuerOriginURL
    *   The origin of the issuer, which is a base URL containing the domain name of the issuer.
    *
    * @param $issuerName
    *   The human-readable name of the issuing agent.
    *
    * @param $issuerOrganization
    *   The organization for which the badge is issued, which is optional.
    *
    * @param $issuerEmail
    *   The optional human-monitored email address associated with the issuer.
    *
    * @param $issuedUnixTime
    *   An optional date when the badge was issued; the current time is used if none is provided.  This is an integer value representing a Unix timestamp, i.e., measured as the number of seconds since the Unix Epoch (January 1, 1970, 00:00:00 GMT).
    *
    * @param $evidenceURL
    *   The optional user-specific URL with information about this specific badge instance. The referenced content should contain information about how the specific user earned the badge.
    *
    * @param $expiresUnixTime
    *   The optional date when the badge expires. If zero, the badge never expires.  This is an integer value representing a Unix timestamp, i.e., measured as the number of seconds since the Unix Epoch (January 1, 1970, 00:00:00 GMT).
    *
    * @param $assertionJSON
    *   The JSON-encoded assertion of the badge once it is issued.
    */
    public function __construct(
        $badgeID,
        $issuedID = NULL,
        $userID = NULL,
        $badgeName = NULL,
        $description = NULL,
        $imageURL = NULL,
        $criteriaURL = NULL,
        $issuerOriginURL = NULL,
        $issuerName = NULL,
        $issuerOrganization = NULL,
        $issuerEmail = NULL,
        $issuedUnixTime = 0,
        $evidenceURL = NULL,
        $expiresUnixTime = 0,
        $assertionJSON = NULL)
    {
        if (!$issuedUnixTime)
        {
            $issuedUnixTime = time();
        }

        if (!$issuedID)
        {
            $issuedID = static::CreateBadgeIssuedID($badgeID, $userID);
        }

        if (!$issuerOriginURL)
        {
            $issuerOriginURL = static::GetCurrentDomainURL();
        }

        $this->badgeID = $badgeID;
        $this->issuedID = $issuedID;
        $this->userID = $userID;
        $this->badgeName = $badgeName;
        $this->description = $description;
        $this->imageURL = $imageURL;
        $this->criteriaURL = $criteriaURL;
        $this->issuerOriginURL = $issuerOriginURL;
        $this->issuerName = $issuerName;
        $this->issuerOrganization = $issuerOrganization;
        $this->issuerEmail = $issuerEmail;
        $this->issuedUnixTime = $issuedUnixTime;
        $this->expiresUnixTime = $expiresUnixTime;
        $this->evidenceURL = $evidenceURL;
        $this->assertionJSON = $assertionJSON;
    }

    /**
    * Creates the JSON-encoded assertion-data of the badge from the various data-fields of this class.
    *
    * @param $includeRootUrlPrefix
    *   An optional boolean value indicating whether to include the application-root relative path in the URL (TRUE) or leave it out (FALSE).
    *
    * @return
    *   A string representing the JSON-encoded assertion data.  In addition to returning the created JSON data, the value is also stored in the @ref $assertionJSON field of this class.
    */
    public function CreateAssertionJSON($includeRootUrlPrefix = FALSE)
    {
        if (!$this->userID)
        {
            return NULL;  // Failure: "recipient" is a required badge-value
        }

        if (!$this->issuedUnixTime)
        {
            $this->issuedUnixTime = time();
        }

        if (!$this->badgeName)
        {
            $this->badgeName = 'Default Name';
        }

        if (!$this->description)
        {
            $this->description = 'Default Description';
        }

        if (!$this->imageURL)
        {
            // URL for image representing the badge; image must be in PNG format
            // and should be 90x90 pixels, with a maximum file size of 256KB
            $this->imageURL = 'data/badges/';
            if ($this->badgeID)
            {
                $this->imageURL .= $this->badgeID . '/badge.png';
            }
            else
            {
                $this->imageURL .= 'example/badge.png';
            }
        }

        if (!$this->criteriaURL)
        {
            // URL describing the badge and criteria for earning the
            // badge (i.e., not the specific instance of the badge)
            $this->criteriaURL = 'data/badges/';
            if ($this->badgeID)
            {
                $this->criteriaURL .= $this->badgeID . '/criteria.html';
            }
            else
            {
                $this->criteriaURL .= 'example/criteria.html';
            }
        }

        // Add the relative-path prefix to the URLs
        // NOTE: If the domain is not present, the issuer-origin will be used.
        // HINT: If the root URL is requested for inclusion, the domain-name portion of the URL is currently also included for the criteria and evidence URLs, since beta.openbadges.org does not seem to always prefix the issuer-origin as one would expect.
        $this->imageURL = static::GetQualifiedURL(
            $this->imageURL, $includeRootUrlPrefix, FALSE);
        $this->criteriaURL = static::GetQualifiedURL(
            $this->criteriaURL, $includeRootUrlPrefix, $includeRootUrlPrefix);
        $this->evidenceURL = static::GetQualifiedURL(
            $this->evidenceURL, $includeRootUrlPrefix, $includeRootUrlPrefix);

        if (!$this->issuerOriginURL)
        {
            $this->issuerOriginURL = static::GetCurrentDomainURL();
        }

        if (!$this->issuerName)
        {
            $this->issuerName = 'Example Issuer';
        }

        $issuerData = array(
            'origin' => (string)$this->issuerOriginURL,
            'name' => (string)$this->issuerName);
        if ($this->issuerOrganization)
        {
            $issuerData['org'] = (string)$this->issuerOrganization;  // optional
        }

        if ($this->issuerEmail)
        {
            $issuerData['contact'] = (string)$this->issuerEmail;  // optional
        }

        $badgeData = array(
            'version' => '0.5.0',
            'name' => (string)$this->badgeName,
            'image' => (string)$this->imageURL,
            'description' => (string)$this->description,
            'criteria' => (string)$this->criteriaURL,
            'issuer' => $issuerData);

        $assertionData = array(
            'recipient' => (string)$this->userID);
        if (self::HASH_USER_ID)
        {
            if (strpos($this->userID, '$') !== FALSE)  // e.g., 'sha256$2ad891a61112bb953171416acc9cfe2484d59a45a3ed574a1ca93b47d07629fe'
            {
                $assertionData['salt'] = (string)self::HASH_USER_ID;
            }
        }

        if ($this->evidenceURL)
        {
            $assertionData['evidence'] = (string)$this->evidenceURL;  // optional
        }

        if ($this->expiresUnixTime)
        {
            $assertionData['expires'] = (string)(date('Y-m-d', $this->expiresUnixTime));  // optional
        }

        $assertionData['issued_on'] = (string)(date('Y-m-d', $this->issuedUnixTime));
        $assertionData['badge'] = $badgeData;

        $this->assertionJSON = json_encode($assertionData);
        return $this->assertionJSON;
    }

    /**
    * Persists the badge's JSON-encoded assertion-data, such that it can be retrieved at some future time.
    *
    * @param $includeAbsoluteURL
    *   An optional boolean value indicating whether the return value should specify a relative path (FALSE) or a qualified URL (TRUE).
    *
    * @return
    *   A string representing the storage location of the JSON-encoded assertion data.
    */
    public function SaveAssertionJSON($includeAbsoluteURL = FALSE)
    {
        $assertionJSON = $this->assertionJSON;
        if (!$assertionJSON)
        {
            $assertionJSON = $this->CreateAssertionJSON($includeAbsoluteURL);
            if (!$assertionJSON)
            {
                return NULL;  // Failure
            }
        }

        // Save the JSON data to disk
        $relativeURL = 'data/assertions/' . $this->issuedID . '.json';
        if (file_put_contents((APP_ROOT . '/' . $relativeURL), $assertionJSON) === FALSE)
        {
            return NULL;  // Failure
        }

        if (!$includeAbsoluteURL)
        {
            return $relativeURL;  // Success: return relative URL
        }

        // Return the URL of where the JSON data can be retrieved
        $assertionURL = static::GetQualifiedURL($relativeURL, TRUE, TRUE);
        return $assertionURL;  // Success: return full URL
    }
}
