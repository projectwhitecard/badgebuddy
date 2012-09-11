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
* Extends the base @ref BadgeData class to illustrate how additional badge
* definitions can be specified through PHP code.
*/

// Include related files
require_once APP_ROOT . '/includes/BadgeData.inc.php';

/**
* Defines the data that is associated with this specific example badge.
*
* Note: A new type of badge can be defined either through code by deriving from @ref BadgeData (as is done for @ref BadgeDataExample), or define through JSON/XML data that the @ref BadgeDataTemplate can interpret.
*/
class BadgeDataExample extends BadgeData
{
    /**
    * Defines the identifier of this specific badge.
    */
    const BADGE_ID = '0817c9045c8f4861b478348719dbff91';

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
        if (isset($badgeID)
            && ($badgeID !== self::BADGE_ID))
        {
            return FALSE;
        }

        return ($proof === 'b9d5a32cc3b14ce08eb078eb43adc0be');
    }

    /**
    * Creates an instance of this specific example badge being issued.
    *
    * @param $issuedID
    *   Optional identifier of the badge instance that is issued to a specific user, which will be auto-generated if not specified.
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
    */
    public function __construct(
        $issuedID = NULL,
        $userID = NULL,
        $issuedUnixTime = 0,
        $evidenceURL = NULL,
        $expiresUnixTime = 0)
    {
        parent::__construct(
            self::BADGE_ID,
            $issuedID,
            $userID,
            'Example Badge',  // $badgeName (required)
            'Interacted with a badge example.',  // $description (required)
            'data/badges/example/badge.png',  // $imageURL (required)
            'data/badges/example/criteria.html',  // $criteriaURL, e.g., '/criteria/badge.html' or 'http://example.com/criteria/badge.html' (required)
            '',  // $issuerOriginURL, e.g., 'http://example.com' (required, but the base class can provide a default)
            'Example Academy',  // $issuerName (required, but the base class can provide a default)
            '',  // $issuerOrganization (optional)
            '',  // $issuerEmail (optional)
            $issuedUnixTime,
            $evidenceURL,
            $expiresUnixTime);
    }
}
