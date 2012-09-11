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
* Extends the base @ref BadgeData class so that additional badge definitions
* can be specified using XML and JSON data files, instead of through PHP code.
*/

// Include related files
require_once APP_ROOT . '/includes/BadgeData.inc.php';

/**
* Defines the data that is associated with a specific template badge, which can be loaded from manifest files (instead of defined through code).
*
* Note: A new type of badge can be defined either through code by deriving from @ref BadgeData (as is done for @ref BadgeDataExample), or define through JSON/XML data that the @ref BadgeDataTemplate can interpret.
*/
class BadgeDataTemplate extends BadgeData
{
    /**
    * Determines whether the provided proof is valid for issuing this specific badge.
    *
    * @param $proof
    *   The proof provided by the client who claims she may get the specified badge.
    *
    * @param $userEmail
    *   The email address of the user who is claiming the badge, which may be used as part of validation.
    *
    * @param $badgeID
    *   The optional identifier of the current badge to be checked.
    *
    * @return
    *   A boolean value indicating whether the proof is valid (TRUE) or invalid (FALSE).
    */
    public static function IsValidProof($proof, $userEmail = '', $badgeID = NULL)
    {
        if (!isset($badgeID)
            || (!static::IsBadgeAvailable($badgeID)))
        {
            return FALSE;
        }

        $manifest = static::LoadTemplateManifest($badgeID);
        if (!$manifest)
        {
            return FALSE;
        }

        if (isset($manifest['proof']))
        {
            return ($proof === $manifest['proof']);
        }

        return TRUE;
    }

    /**
    * Determines whether the specified identifier is available for issuing a template badge.
    *
    * @param $badgeID
    *   The identifier of the template badge that might be issued.
    *
    * @return
    *   A boolean value indicating whether the badge is available to be issued (TRUE) or unavailable (FALSE).
    */
    public static function IsBadgeAvailable($badgeID)
    {
        if (!isset($badgeID)
            || ($badgeID === ''))
        {
            return FALSE;
        }

        return file_exists(static::GetTemplateDirectory($badgeID));
    }

    /**
    * Retrieves a collection of identifiers of template badges that may be available to be issued.
    *
    * @return
    *   Returns an array containing identifiers of template badges that may be available to be issued.
    */
    public static function GetCatalogBadgeIDs()
    {
        $badgeIDs = array();
        $basePath = dirname(static::GetTemplateDirectory('dummyBadgeID'));
        foreach (scandir($basePath) as $badgeID)
        {
            if ((strpos($badgeID, '.') !== 0)  // Ignore hidden, current and parent folders
                && is_dir($basePath . '/' . $badgeID))  // Ignore filenames
            {
                array_push($badgeIDs, $badgeID);
            }
        }

        return $badgeIDs;
    }

    /**
    * Retrieves a collection of template badges that may be available to be issued.
    *
    * @return
    *   Returns an array containing instances of type @ref BadgeDataTemplate (or a derivative) that describes template badges that may be available to be issued.
    */
    public static function GetCatalogBadges()
    {
        $badges = array();
        foreach (static::GetCatalogBadgeIDs() as $badgeID)
        {
            array_push(
                $badges,
                static::GetCatalogBadgeData(new BadgeDataTemplate($badgeID)));
        }

        return $badges;
    }

    /**
    * Gets the directory that contains the files belonging to the specified template badge.
    *
    * @param $badgeID
    *   The identifier of the template badge.
    *
    * @param $includeAppRoot
    *   An optional boolean value indicating whether to include (TRUE) or exclude (FALSE) the application-root path in the directory name.
    *
    * @return
    *   A string that specifies the directory of the badge template.
    */
    protected static function GetTemplateDirectory($badgeID, $includeAppRoot = TRUE)
    {
        if (!isset($badgeID)
            || ($badgeID === ''))
        {
            return NULL;
        }

        $path = 'data/badges/catalog/' . preg_replace('/[^a-zA-Z0-9~_\-]+/', '', $badgeID);
        if ($includeAppRoot)
        {
            $path = (APP_ROOT . '/' . $path);
        }

        return $path;
    }

    /**
    * Loads the data contained in the manifest file of the specified template badge.
    *
    * @param $badgeID
    *   The identifier of the badge to be issued, which can be thought of as the ID of a template on which the actual issued badge instance is based.
    *
    * @return
    *   Upon success, returns an associative array containing values that have been loaded from the manifest file, or NULL if the manifest cannot be loaded.
    */
    protected static function LoadTemplateManifest($badgeID)
    {
        $filePrefix = static::GetTemplateDirectory($badgeID) . '/manifest';
        $filename = $filePrefix . '.json';
        if (file_exists($filename))
        {
            // Load the JSON data as an associative array
            // NOTE: The JSON file MUST be stored as UTF-8 without BOM
            return (array)json_decode(file_get_contents($filename));
        }

        $filename = $filePrefix . '.xml';
        if (file_exists($filename))
        {
            // Load the XML data and convert to an associative array
            $xml = new DOMDocument();
            if ($xml->load(realpath($filename)))
            {
                $manifest = array();
                foreach ($xml->documentElement->childNodes as $node)
                {
                    $manifest[$node->nodeName] = $node->nodeValue;
                }

                return $manifest;
            }
        }

        return NULL;
    }

    /**
    * Creates an instance of a specific template badge being issued.
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
        $issuedUnixTime = 0,
        $evidenceURL = NULL,
        $expiresUnixTime = 0,
        $assertionJSON = NULL)
    {
        $badgeName = NULL;
        $description = NULL;
        $imageURL = NULL;
        $criteriaURL = NULL;
        $issuerOriginURL = NULL;
        $issuerName = NULL;
        $issuerOrganization = NULL;
        $issuerEmail = NULL;
        $manifest = static::LoadTemplateManifest($badgeID);
        if ($manifest)
        {
            if (isset($manifest['badgeID']))
            {
                $badgeID = $manifest['badgeID'];  // Allow ID overrides
            }

            if (isset($manifest['badgeName']))
            {
                $badgeName = $manifest['badgeName'];
            }

            if (isset($manifest['description']))
            {
                $description = $manifest['description'];
            }

            if (isset($manifest['imageURL']))
            {
                $imageURL = $manifest['imageURL'];
            }

            if (isset($manifest['criteriaURL']))
            {
                $criteriaURL = $manifest['criteriaURL'];
            }

            if (isset($manifest['issuerOriginURL']))
            {
                $issuerOriginURL = $manifest['issuerOriginURL'];
            }

            if (isset($manifest['issuerName']))
            {
                $issuerName = $manifest['issuerName'];
            }

            if (isset($manifest['issuerOrganization']))
            {
                $issuerOrganization = $manifest['issuerOrganization'];
            }

            if (isset($manifest['issuerEmail']))
            {
                $issuerEmail = $manifest['issuerEmail'];
            }

            if (isset($manifest['expiresUnixTime'])
                && (($expiresUnixTime === 0)
                    || ($expiresUnixTime === '')
                    || ($expiresUnixTime === NULL)))
            {
                $expiresUnixTime = $manifest['expiresUnixTime'];
            }
        }

        if (!$imageURL)
        {
            // URL for image representing the badge; image must be in PNG format
            // and should be 90x90 pixels, with a maximum file size of 256KB
            if ($badgeID)
            {
                $imageURL = static::GetTemplateDirectory($badgeID, FALSE) . '/badge.png';
            }
            else
            {
                $imageURL = 'data/badges/example/badge.png';
            }
        }

        if (!$criteriaURL)
        {
            // URL describing the badge and criteria for earning the
            // badge (i.e., not the specific instance of the badge)
            if ($badgeID)
            {
                $criteriaURL = static::GetTemplateDirectory($badgeID, FALSE) . '/criteria.html';
            }
            else
            {
                $criteriaURL = 'data/badges/example/criteria.html';
            }
        }

        parent::__construct(
            $badgeID,
            $issuedID,
            $userID,
            $badgeName,
            $description,
            $imageURL,
            $criteriaURL,
            $issuerOriginURL,
            $issuerName,
            $issuerOrganization,
            $issuerEmail,
            $issuedUnixTime,
            $evidenceURL,
            $expiresUnixTime,
            $assertionJSON);
    }
}
