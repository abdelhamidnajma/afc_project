<?php
/**
 * Input validatie functies
 * Deze functies zorgen voor het valideren en sanitiseren van gebruikersinput
 */

// Controleer of functie al bestaat om dubbele definities te voorkomen
if (!function_exists('validateUserInput')) {
    /**
     * Valideer gebruikersinput voor registratie/bijwerken
     * @param array $data Array met gebruikersgegevens
     * @return bool True als validatie succesvol is
     * @throws Exception Als validatie faalt
     */
    function validateUserInput($data) {
        // Valideer gebruikersnaam
        if (!isset($data['username']) || !preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $data['username'])) {
            throw new Exception('Gebruikersnaam moet 3-50 tekens lang zijn en mag alleen letters, cijfers, underscores en streepjes bevatten');
        }

        // Valideer e-mailadres
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Ongeldig e-mailadres');
        }

        // Valideer wachtwoord indien opgegeven
        if (isset($data['password'])) {
            validatePassword($data['password']);
        }

        return true;
    }
}

/**
 * Valideer wachtwoord sterkte
 * @param string $password Het te valideren wachtwoord
 * @return bool True als wachtwoord voldoet aan eisen
 * @throws Exception Als wachtwoord niet voldoet aan eisen
 */
function validatePassword($password) {
    // Controleer minimale lengte
    if (strlen($password) < 8) {
        throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn');
    }

    // Controleer op hoofdletters
    if (!preg_match('/[A-Z]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één hoofdletter bevatten');
    }

    // Controleer op kleine letters
    if (!preg_match('/[a-z]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één kleine letter bevatten');
    }

    // Controleer op cijfers
    if (!preg_match('/[0-9]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één cijfer bevatten');
    }

    // Controleer op speciale tekens
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        throw new Exception('Wachtwoord moet minimaal één speciaal teken bevatten');
    }

    return true;
}

/**
 * Sanitiseer gebruikersinput om XSS aanvallen te voorkomen
 * @param mixed $data Input data (string of array)
 * @return mixed Gesanitiseerde data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valideer verplichte velden
 * @param array $data Array met data
 * @param array $fields Array met verplichte veldnamen
 * @return bool True als alle verplichte velden aanwezig zijn
 * @throws Exception Als verplichte velden ontbreken
 */
function validateRequired($data, $fields) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Veld '{$field}' is verplicht");
        }
    }
    return true;
}

/**
 * Valideer lengte van een waarde
 * @param string $value De te valideren waarde
 * @param int $min Minimale lengte
 * @param int $max Maximale lengte
 * @param string $fieldName Naam van het veld (voor foutmelding)
 * @return bool True als lengte binnen bereik valt
 * @throws Exception Als lengte buiten bereik valt
 */
function validateLength($value, $min, $max, $fieldName) {
    $length = mb_strlen($value);
    if ($length < $min || $length > $max) {
        throw new Exception("{$fieldName} moet tussen {$min} en {$max} tekens lang zijn");
    }
    return true;
}

/**
 * Valideer datum formaat
 * @param string $date Datum string
 * @param string $format Gewenst datum formaat (standaard: Y-m-d)
 * @return bool True als datum geldig is
 * @throws Exception Als datum ongeldig is
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        throw new Exception("Ongeldige datum. Gebruik het formaat: {$format}");
    }
    return true;
}

/**
 * Valideer tijd formaat
 * @param string $time Tijd string
 * @param string $format Gewenst tijd formaat (standaard: H:i)
 * @return bool True als tijd geldig is
 * @throws Exception Als tijd ongeldig is
 */
function validateTime($time, $format = 'H:i') {
    $t = DateTime::createFromFormat($format, $time);
    if (!$t || $t->format($format) !== $time) {
        throw new Exception("Ongeldige tijd. Gebruik het formaat: {$format}");
    }
    return true;
}

/**
 * Valideer numerieke waarde
 * @param mixed $value De te valideren waarde
 * @param string $fieldName Naam van het veld (voor foutmelding)
 * @return bool True als waarde numeriek is
 * @throws Exception Als waarde niet numeriek is
 */
function validateNumeric($value, $fieldName) {
    if (!is_numeric($value)) {
        throw new Exception("{$fieldName} moet een nummer zijn");
    }
    return true;
}

/**
 * Valideer e-mailadres
 * @param string $email E-mailadres
 * @return bool True als e-mailadres geldig is
 * @throws Exception Als e-mailadres ongeldig is
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Ongeldig e-mailadres");
    }
    return true;
}

/**
 * Valideer URL
 * @param string $url URL string
 * @return bool True als URL geldig is
 * @throws Exception Als URL ongeldig is
 */
function validateURL($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception("Ongeldige URL");
    }
    return true;
}

/**
 * Valideer telefoonnummer
 * @param string $phone Telefoonnummer
 * @return bool True als telefoonnummer geldig is
 * @throws Exception Als telefoonnummer ongeldig is
 */
function validatePhone($phone) {
    // Verwijder alle niet-numerieke tekens
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Basis telefoonnummer validatie (kan aangepast worden op basis van vereisten)
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        throw new Exception("Ongeldig telefoonnummer");
    }
    return true;
}

/**
 * Valideer Nederlandse postcode
 * @param string $postalCode Postcode
 * @return bool True als postcode geldig is
 * @throws Exception Als postcode ongeldig is
 */
function validatePostalCode($postalCode) {
    // Nederlands postcode formaat: 1234 AB
    if (!preg_match('/^[1-9][0-9]{3}\s?[A-Z]{2}$/i', $postalCode)) {
        throw new Exception("Ongeldige postcode. Gebruik het formaat: 1234 AB");
    }
    return true;
}

/**
 * Valideer gebruikersrol
 * @param string $role Gebruikersrol
 * @return bool True als rol geldig is
 * @throws Exception Als rol ongeldig is
 */
function validateRole($role) {
    $allowedRoles = ['admin', 'editor', 'user'];
    if (!in_array($role, $allowedRoles)) {
        throw new Exception("Ongeldige rol. Toegestane rollen: " . implode(', ', $allowedRoles));
    }
    return true;
}

/**
 * Valideer categorie
 * @param string $category Categorie
 * @param array $allowedCategories Toegestane categorieën
 * @return bool True als categorie geldig is
 * @throws Exception Als categorie ongeldig is
 */
function validateCategory($category, $allowedCategories) {
    if (!in_array($category, $allowedCategories)) {
        throw new Exception("Ongeldige categorie. Toegestane categorieën: " . implode(', ', $allowedCategories));
    }
    return true;
}

/**
 * Valideer status
 * @param string $status Status
 * @param array $allowedStatuses Toegestane statussen
 * @return bool True als status geldig is
 * @throws Exception Als status ongeldig is
 */
function validateStatus($status, $allowedStatuses) {
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception("Ongeldige status. Toegestane statussen: " . implode(', ', $allowedStatuses));
    }
    return true;
}

/**
 * Valideer bestandsgrootte
 * @param int $fileSize Bestandsgrootte in bytes
 * @param int $maxSize Maximale grootte in bytes
 * @param string $fieldName Naam van het veld (voor foutmelding)
 * @return bool True als bestandsgrootte acceptabel is
 * @throws Exception Als bestandsgrootte te groot is
 */
function validateFileSize($fileSize, $maxSize, $fieldName) {
    if ($fileSize > $maxSize) {
        $maxSizeMB = round($maxSize / 1024 / 1024, 2);
        throw new Exception("{$fieldName} is te groot. Maximale grootte: {$maxSizeMB} MB");
    }
    return true;
}

/**
 * Valideer bestandstype
 * @param string $fileName Bestandsnaam
 * @param array $allowedTypes Toegestane bestandstypen
 * @param string $fieldName Naam van het veld (voor foutmelding)
 * @return bool True als bestandstype toegestaan is
 * @throws Exception Als bestandstype niet toegestaan is
 */
function validateFileType($fileName, $allowedTypes, $fieldName) {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception("{$fieldName} heeft een ongeldig bestandstype. Toegestane types: " . implode(', ', $allowedTypes));
    }
    return true;
}

/**
 * Valideer nieuws input
 * @param array $data Array met nieuwsgegevens
 * @return bool True als validatie succesvol is
 * @throws Exception Als validatie faalt
 */
function validateNewsInput($data) {
    $allowedCategories = ['general', 'team', 'match', 'event', 'club'];
    $allowedStatuses = ['draft', 'published', 'archived'];

    // Valideer verplichte velden
    validateRequired($data, ['title', 'content']);
    
    // Valideer lengtes
    validateLength($data['title'], 5, 255, 'Titel');
    validateLength($data['content'], 10, 10000, 'Content');
    
    if (isset($data['excerpt'])) {
        validateLength($data['excerpt'], 0, 500, 'Samenvatting');
    }
    
    // Valideer categorie en status
    if (isset($data['category'])) {
        validateCategory($data['category'], $allowedCategories);
    }
    
    if (isset($data['status'])) {
        validateStatus($data['status'], $allowedStatuses);
    }

    return true;
}

/**
 * Valideer team input
 * @param array $data Array met teamgegevens
 * @return bool True als validatie succesvol is
 * @throws Exception Als validatie faalt
 */
function validateTeamInput($data) {
    $allowedCategories = ['senioren', 'junioren', 'amateurs', 'vrouwen', 'meisjes'];
    $allowedStatuses = ['active', 'inactive', 'archived'];

    // Valideer verplichte velden
    validateRequired($data, ['name', 'category']);
    
    // Valideer lengtes
    validateLength($data['name'], 2, 255, 'Teamnaam');
    validateLength($data['category'], 2, 100, 'Categorie');
    
    if (isset($data['division'])) {
        validateLength($data['division'], 1, 50, 'Divisie');
    }
    
    if (isset($data['coach'])) {
        validateLength($data['coach'], 2, 100, 'Coach');
    }

    // Valideer categorie en status
    validateCategory($data['category'], $allowedCategories);
    
    if (isset($data['status'])) {
        validateStatus($data['status'], $allowedStatuses);
    }

    return true;
}

/**
 * Valideer instellingen input
 * @param array $data Array met instellingen
 * @return bool True als validatie succesvol is
 * @throws Exception Als validatie faalt
 */
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

/**
 * Valideer gebruikersinput (herdefinitie voor consistentie)
 * @param array $data Array met gebruikersgegevens
 * @return bool True als validatie succesvol is
 * @throws Exception Als validatie faalt
 */
function validateUserInput($data) {
    $allowedRoles = ['admin', 'editor', 'user'];

    validateRequired($data, ['username', 'email']);
    
    // Valideer gebruikersnaam
    if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $data['username'])) {
        throw new Exception('Gebruikersnaam moet 3-50 tekens lang zijn en mag alleen letters, cijfers, underscores en streepjes bevatten');
    }
    
    // Valideer e-mailadres
    validateEmail($data['email']);
    
    // Valideer wachtwoord indien opgegeven
    if (isset($data['password'])) {
        validatePassword($data['password']);
    }
    
    // Valideer rol indien opgegeven
    if (isset($data['role'])) {
        validateRole($data['role']);
    }

    return true;
}
?> 