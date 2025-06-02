<?php
/**
 * Input validation functions
 */

if (!function_exists('validateUserInput')) {
function validateUserInput($data) {
    // Validate username
    if (!isset($data['username']) || !preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $data['username'])) {
        throw new Exception('Gebruikersnaam moet 3-50 tekens lang zijn en mag alleen letters, cijfers, underscores en streepjes bevatten');
    }

    // Validate email
    if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ongeldig e-mailadres');
    }

    // Validate password if provided
    if (isset($data['password'])) {
        validatePassword($data['password']);
    }

    return true;
}
}

function validatePassword($password) {
    if (strlen($password) < 8) {
        throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn');
    }

    if (!preg_match('/[A-Z]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één hoofdletter bevatten');
    }

    if (!preg_match('/[a-z]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één kleine letter bevatten');
    }

    if (!preg_match('/[0-9]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één cijfer bevatten');
    }

    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één speciaal teken bevatten');
    }

    return true;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateRequired($data, $fields) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Veld '{$field}' is verplicht");
        }
    }
    return true;
}

function validateLength($value, $min, $max, $fieldName) {
    $length = mb_strlen($value);
    if ($length < $min || $length > $max) {
        throw new Exception("{$fieldName} moet tussen {$min} en {$max} tekens lang zijn");
    }
    return true;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        throw new Exception("Ongeldige datum. Gebruik het formaat: {$format}");
    }
    return true;
}

function validateTime($time, $format = 'H:i') {
    $t = DateTime::createFromFormat($format, $time);
    if (!$t || $t->format($format) !== $time) {
        throw new Exception("Ongeldige tijd. Gebruik het formaat: {$format}");
    }
    return true;
}

function validateNumeric($value, $fieldName) {
    if (!is_numeric($value)) {
        throw new Exception("{$fieldName} moet een nummer zijn");
    }
    return true;
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Ongeldig e-mailadres");
    }
    return true;
}

function validateURL($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception("Ongeldige URL");
    }
    return true;
}

function validatePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Basic phone number validation (can be adjusted based on requirements)
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        throw new Exception("Ongeldig telefoonnummer");
    }
    return true;
}

function validatePostalCode($postalCode) {
    // Dutch postal code format: 1234 AB
    if (!preg_match('/^[1-9][0-9]{3}\s?[A-Z]{2}$/i', $postalCode)) {
        throw new Exception("Ongeldige postcode. Gebruik het formaat: 1234 AB");
    }
    return true;
}

function validateSlug($slug) {
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        throw new Exception("Slug mag alleen kleine letters, cijfers en streepjes bevatten");
    }
    return true;
}

function validateRole($role) {
    $validRoles = ['admin', 'editor', 'user'];
    if (!in_array($role, $validRoles)) {
        throw new Exception("Ongeldige rol. Geldige rollen zijn: " . implode(', ', $validRoles));
    }
    return true;
}

function validateStatus($status, $validStatuses) {
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Ongeldige status. Geldige statussen zijn: " . implode(', ', $validStatuses));
    }
    return true;
}

function validateFileUpload($file, $allowedTypes, $maxSize) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception("Ongeldige bestandsupload");
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception("Bestand is te groot");
        case UPLOAD_ERR_PARTIAL:
            throw new Exception("Bestand is niet volledig geüpload");
        case UPLOAD_ERR_NO_FILE:
            throw new Exception("Geen bestand geüpload");
        case UPLOAD_ERR_NO_TMP_DIR:
            throw new Exception("Tijdelijke map ontbreekt");
        case UPLOAD_ERR_CANT_WRITE:
            throw new Exception("Kan bestand niet schrijven");
        case UPLOAD_ERR_EXTENSION:
            throw new Exception("Upload gestopt door extensie");
        default:
            throw new Exception("Onbekende upload fout");
    }

    if ($file['size'] > $maxSize) {
        throw new Exception("Bestand is te groot. Maximum grootte is " . ($maxSize / 1024 / 1024) . "MB");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Ongeldig bestandstype. Toegestane types zijn: " . implode(', ', $allowedTypes));
    }

    return true;
}

// News validation
function validateNewsInput($data) {
    $allowedCategories = ['team', 'club', 'events'];
    $allowedStatuses = ['draft', 'published'];

    validateRequired($data['title'], 'Titel');
    validateLength($data['title'], 3, 255, 'Titel');
    
    validateRequired($data['content'], 'Inhoud');
    validateLength($data['content'], 10, 10000, 'Inhoud');
    
    if (isset($data['category'])) {
        validateCategory($data['category'], $allowedCategories);
    }
    
    if (isset($data['status'])) {
        validateStatus($data['status'], $allowedStatuses);
    }

    return true;
}

// Match validation
function validateMatchInput($data) {
    $allowedStatuses = ['scheduled', 'live', 'completed', 'cancelled'];

    validateRequired($data['home_team_id'], 'Thuisteam');
    validateRequired($data['away_team_id'], 'Uitteam');
    validateRequired($data['match_date'], 'Wedstrijddatum');
    
    validateInt($data['home_team_id']);
    validateInt($data['away_team_id']);
    validateDateTime($data['match_date']);
    
    if (isset($data['status'])) {
        validateStatus($data['status'], $allowedStatuses);
    }
    
    if (isset($data['home_score'])) {
        validateInt($data['home_score']);
    }
    
    if (isset($data['away_score'])) {
        validateInt($data['away_score']);
    }

    return true;
}

// Team validation
function validateTeamInput($data) {
    $allowedCategories = ['senior', 'junior', 'women'];

    validateRequired($data['name'], 'Teamnaam');
    validateLength($data['name'], 2, 100, 'Teamnaam');
    
    validateRequired($data['category'], 'Categorie');
    validateCategory($data['category'], $allowedCategories);
    
    if (isset($data['division'])) {
        validateLength($data['division'], 1, 50, 'Divisie');
    }
    
    if (isset($data['coach'])) {
        validateLength($data['coach'], 2, 100, 'Coach');
    }

    return true;
}

// Settings validation
function validateSettingsInput($data) {
    foreach ($data as $key => $value) {
        switch ($key) {
            case 'website_name':
                validateRequired($value, 'Website naam');
                validateLength($value, 2, 100, 'Website naam');
                break;
            case 'contact_email':
                validateRequired($value, 'Contact e-mail');
                validateEmail($value);
                break;
            case 'phone_number':
                validateRequired($value, 'Telefoonnummer');
                validatePhone($value);
                break;
            case 'facebook_url':
            case 'twitter_url':
            case 'instagram_url':
                if (!empty($value)) {
                    validateURL($value);
                }
                break;
        }
    }
    return true;
}

// User validation
function validateUserInput($data) {
    $allowedRoles = ['admin', 'editor', 'user'];

    validateRequired($data['username'], 'Gebruikersnaam');
    // Removed call to validateUsername() as it is not defined and causes errors
    
    validateRequired($data['email'], 'E-mailadres');
    validateEmail($data['email']);
    
    if (isset($data['password'])) {
        validatePassword($data['password']);
    }
    
    if (isset($data['role'])) {
        validateRole($data['role']); // Use validateRole instead of validateCategory for role validation
    }

    return true;
}
?> 