<?php
/**
 * Helper Functions
 * 
 * Common utility functions used throughout the application.
 */

/**
 * Format currency value
 * 
 * @param float|int $amount Amount to format
 * @param string $currency Currency symbol (default: MAD)
 * @return string Formatted amount
 */
function formatCurrency(float|int $amount, string $currency = 'MAD'): string {
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

/**
 * Format percentage
 * 
 * @param float|int $value Value to format
 * @param int $decimals Number of decimal places
 * @return string Formatted percentage
 */
function formatPercentage(float|int $value, int $decimals = 2): string {
    return number_format($value, $decimals, ',', ' ') . '%';
}

/**
 * Format date in French format
 * 
 * @param string|null $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string {
    if (empty($date)) {
        return '-';
    }
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Format datetime in French format
 * 
 * @param string|null $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime(?string $datetime): string {
    if (empty($datetime)) {
        return '-';
    }
    
    $dateObj = new DateTime($datetime);
    return $dateObj->format('d/m/Y à H:i');
}

/**
 * Calculate age from date of birth
 * 
 * @param string $dateNaissance Date of birth (Y-m-d format)
 * @return int Age in years
 */
function calculateAge(string $dateNaissance): int {
    $birthDate = new DateTime($dateNaissance);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}

/**
 * Calculate debt ratio
 * 
 * @param float $charges Monthly charges
 * @param float $revenus Monthly income
 * @return float Debt ratio as percentage
 */
function calculateDebtRatio(float $charges, float $revenus): float {
    if ($revenus <= 0) {
        return 100;
    }
    return ($charges / $revenus) * 100;
}

/**
 * Calculate monthly payment for a loan
 * 
 * @param float $principal Loan amount
 * @param float $annualRate Annual interest rate (percentage)
 * @param int $durationMonths Loan duration in months
 * @return float Monthly payment
 */
function calculateMonthlyPayment(float $principal, float $annualRate, int $durationMonths): float {
    if ($principal <= 0 || $durationMonths <= 0) {
        return 0;
    }
    
    $monthlyRate = ($annualRate / 100) / 12;
    
    if ($monthlyRate == 0) {
        return $principal / $durationMonths;
    }
    
    return $principal * ($monthlyRate / (1 - pow(1 + $monthlyRate, -$durationMonths)));
}

/**
 * Calculate total cost of a loan
 * 
 * @param float $monthlyPayment Monthly payment
 * @param int $durationMonths Loan duration in months
 * @return float Total cost
 */
function calculateTotalCost(float $monthlyPayment, int $durationMonths): float {
    return $monthlyPayment * $durationMonths;
}

/**
 * Generate unique reference number
 * 
 * @param string $prefix Prefix for the reference
 * @return string Unique reference
 */
function generateReference(string $prefix = 'CR'): string {
    return $prefix . '-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Sanitize input string
 * 
 * @param string|null $input Input string
 * @return string Sanitized string
 */
function sanitize(?string $input): string {
    if ($input === null) {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate CIN format (Moroccan national ID)
 * 
 * @param string $cin CIN to validate
 * @return bool
 */
function isValidCIN(string $cin): bool {
    // Moroccan CIN: 1-2 letters followed by 5-6 digits
    return preg_match('/^[A-Za-z]{1,2}[0-9]{5,6}$/', $cin) === 1;
}

/**
 * Get status badge HTML
 * 
 * @param string $status Status value
 * @param string $type Type of status (credit, user)
 * @return string HTML badge
 */
function getStatusBadge(string $status, string $type = 'credit'): string {
    $classes = [
        'credit' => [
            'approuve' => 'bg-green-100 text-green-800',
            'refuse' => 'bg-red-100 text-red-800',
            'en_attente' => 'bg-yellow-100 text-yellow-800',
            'en_revision' => 'bg-blue-100 text-blue-800',
        ],
        'user' => [
            'actif' => 'bg-green-100 text-green-800',
            'inactif' => 'bg-gray-100 text-gray-800',
        ]
    ];
    
    $labels = [
        'credit' => [
            'approuve' => 'Approuvé',
            'refuse' => 'Refusé',
            'en_attente' => 'En attente',
            'en_revision' => 'En révision',
        ],
        'user' => [
            'actif' => 'Actif',
            'inactif' => 'Inactif',
        ]
    ];
    
    $class = $classes[$type][$status] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$type][$status] ?? ucfirst($status);
    
    return sprintf(
        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
        $class,
        $label
    );
}

/**
 * Get score color class based on score value
 * 
 * @param int $score Score value (0-100)
 * @return string Tailwind color class
 */
function getScoreColorClass(int $score): string {
    if ($score >= 70) {
        return 'text-green-600';
    } elseif ($score >= 50) {
        return 'text-yellow-600';
    }
    return 'text-red-600';
}

/**
 * Get score background color class
 * 
 * @param int $score Score value (0-100)
 * @return string Tailwind background color class
 */
function getScoreBgClass(int $score): string {
    if ($score >= 70) {
        return 'bg-green-500';
    } elseif ($score >= 50) {
        return 'bg-yellow-500';
    }
    return 'bg-red-500';
}

/**
 * Convert professional situation code to French label
 * 
 * @param string $situation Professional situation code
 * @return string French label
 */
function getSituationLabel(string $situation): string {
    $labels = [
        'CDI' => 'CDI (Contrat à durée indéterminée)',
        'CDD' => 'CDD (Contrat à durée déterminée)',
        'Fonctionnaire' => 'Fonctionnaire',
        'Independant' => 'Travailleur indépendant',
        'Sans emploi' => 'Sans emploi',
        'Retraite' => 'Retraité(e)',
    ];
    
    return $labels[$situation] ?? $situation;
}

/**
 * Get employment tenure label
 * 
 * @param int $months Employment tenure in months
 * @return string Human-readable label
 */
function getAncienneteLabel(int $months): string {
    if ($months < 12) {
        return $months . ' mois';
    }
    
    $years = floor($months / 12);
    $remainingMonths = $months % 12;
    
    if ($remainingMonths === 0) {
        return $years . ' an' . ($years > 1 ? 's' : '');
    }
    
    return $years . ' an' . ($years > 1 ? 's' : '') . ' et ' . $remainingMonths . ' mois';
}

/**
 * Truncate text with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncate(string $text, int $length = 50): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Get relative time string
 * 
 * @param string $datetime Datetime string
 * @return string Relative time (e.g., "il y a 2 heures")
 */
function getRelativeTime(string $datetime): string {
    $now = new DateTime();
    $date = new DateTime($datetime);
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    }
    if ($diff->m > 0) {
        return 'il y a ' . $diff->m . ' mois';
    }
    if ($diff->d > 0) {
        return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    }
    if ($diff->h > 0) {
        return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    }
    if ($diff->i > 0) {
        return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }
    
    return 'à l\'instant';
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param array $data Data to send
 * @param int $statusCode HTTP status code
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect with flash message
 * 
 * @param string $url URL to redirect to
 * @param string|null $message Flash message
 * @param string $type Message type
 */
function redirectWith(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message) {
        setFlashMessage($type, $message);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get pagination data
 * 
 * @param int $totalItems Total number of items
 * @param int $currentPage Current page number
 * @param int $perPage Items per page
 * @return array Pagination data
 */
function getPagination(int $totalItems, int $currentPage = 1, int $perPage = 10): array {
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}
