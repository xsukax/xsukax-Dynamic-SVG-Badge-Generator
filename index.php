<?php
/**
 * xsukax Dynamic SVG Badge Generator
 * 
 * A robust SVG badge generator with support for custom labels, messages, and colors.
 * Usage: /index.php?badge=label-message-color1-color2-fontColor.svg
 * 
 * Spaces can be encoded as:
 *  - Underscores (_) which will be converted to spaces
 *  - URL encoded (%20) for spaces
 * 
 * Plus signs (+) are treated as literal characters
 * Note: In URLs, use + directly or encode as %2B for safety
 * 
 * Examples:
 *  /index.php?badge=Build_Status-Tests_Passing-green.svg
 *  /index.php?badge=Code%20Coverage-95%20percent-blue.svg
 *  /index.php?badge=C++-Programming_Language-orange.svg
 *  /index.php?badge=Node%2Ejs-Runtime-blue.svg (using %2B for +)
 * 
 * @author xsukax
 * @version 2.4
 */

// Set appropriate headers
header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: max-age=300');
header('X-Generator: xsukax Dynamic SVG Badge Generator');

// Constants
define('DEFAULT_FONT_SIZE', 11);
define('BADGE_HEIGHT', 20);
define('PADDING', 10);
define('CHAR_WIDTH_RATIO', 0.55);

// Define color maps as constants
const NAMED_COLORS = [
    'red' => '#e05d44', 
    'green' => '#97ca00', 
    'blue' => '#007ec6',
    'yellow' => '#dfb317', 
    'orange' => '#fe7d37', 
    'purple' => '#9f9f9f',
    'pink' => '#ff69b4', 
    'brown' => '#8b4513', 
    'black' => '#000',
    'white' => '#fff', 
    'grey' => '#555', 
    'gray' => '#555',
    'lightgrey' => '#9f9f9f', 
    'lightgray' => '#9f9f9f',
    'darkgrey' => '#333', 
    'darkgray' => '#333',
    'brightgreen' => '#4c1', 
    'brightred' => '#e05d44', 
    'brightblue' => '#007ec6',
    'lightgreen' => '#90ee90', 
    'darkgreen' => '#006400',
    'lightblue' => '#add8e6', 
    'darkblue' => '#00008b',
    'lightyellow' => '#ffffe0', 
    'darkyellow' => '#b8860b',
    'lightred' => '#ffcccb', 
    'darkred' => '#8b0000',
    'cyan' => '#00ffff', 
    'magenta' => '#ff00ff', 
    'lime' => '#00ff00',
    'navy' => '#000080', 
    'teal' => '#008080', 
    'silver' => '#c0c0c0',
    'maroon' => '#800000', 
    'olive' => '#808000', 
    'aqua' => '#00ffff',
    'fuchsia' => '#ff00ff', 
    'success' => '#28a745', 
    'warning' => '#ffc107',
    'danger' => '#dc3545', 
    'info' => '#17a2b8', 
    'primary' => '#007bff',
    'secondary' => '#6c757d'
];

/**
 * Convert underscores to spaces in text
 * URL decoding should happen before this function is called
 * 
 * @param string $text The text to process
 * @return string Text with underscores converted to spaces
 */
function convertUnderscoresToSpaces($text) {
    // Replace underscores with spaces
    return str_replace('_', ' ', $text);
}

/**
 * Parse URL segments to extract badge parameters
 * 
 * @param string $input The input badge string (already URL decoded)
 * @return array Parsed parameters with defaults
 */
function parseUrlSegments($input) {
    // Remove .svg extension if present
    $cleanInput = preg_replace('/\.svg$/i', '', $input);
    
    // Set defaults
    $defaults = [
        'label' => 'xsukax Badge',
        'message' => 'Dynamic Generator',
        'color1' => '#555',
        'color2' => '#4c1',
        'fontColor' => '#fff'
    ];
    
    // Handle empty input
    if (empty($cleanInput)) {
        return $defaults;
    }
    
    // Split by hyphen (main delimiter)
    $parts = explode('-', $cleanInput);
    
    // Need at least 2 parts for label and message
    if (count($parts) < 2) {
        $defaults['label'] = convertUnderscoresToSpaces($parts[0] ?? 'xsukax');
        return $defaults;
    }
    
    // Extract label (first part) and convert underscores
    $defaults['label'] = convertUnderscoresToSpaces($parts[0]);
    
    // Find where colors start by checking from the end
    $colorIndices = [];
    $maxColorCheck = min(3, count($parts) - 1); // Check last 3 parts max
    
    for ($i = count($parts) - 1; $i > 0 && count($colorIndices) < 3; $i--) {
        if (isColorLike($parts[$i])) {
            array_unshift($colorIndices, $i);
        } else if (count($colorIndices) > 0) {
            // If we found colors but this isn't a color, stop looking
            break;
        }
    }
    
    // Determine message end index
    $messageEndIndex = !empty($colorIndices) ? $colorIndices[0] : count($parts);
    
    // Extract message (everything between label and colors)
    if ($messageEndIndex > 1) {
        $messageParts = array_slice($parts, 1, $messageEndIndex - 1);
        if (!empty($messageParts)) {
            $joinedMessage = implode('-', $messageParts);
            // Convert underscores to spaces in message
            $defaults['message'] = convertUnderscoresToSpaces($joinedMessage);
        }
    }
    
    // Extract colors if found
    if (!empty($colorIndices)) {
        foreach ($colorIndices as $index => $partIndex) {
            $color = normalizeColor($parts[$partIndex]);
            switch ($index) {
                case 0:
                    $defaults['color1'] = $color;
                    break;
                case 1:
                    $defaults['color2'] = $color;
                    break;
                case 2:
                    $defaults['fontColor'] = $color;
                    break;
            }
        }
    }
    
    return $defaults;
}

/**
 * Check if a string looks like a color
 * 
 * @param string $str The string to check
 * @return bool True if string appears to be a color
 */
function isColorLike($str) {
    if (empty($str)) {
        return false;
    }
    
    // Don't treat strings with underscores or spaces as colors (they're likely text)
    if (strpos($str, '_') !== false || strpos($str, ' ') !== false) {
        return false;
    }
    
    // Check for hex colors (with or without #)
    if (preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3})?$/i', $str)) {
        return true;
    }
    
    // Check for named colors
    return isset(NAMED_COLORS[strtolower($str)]);
}

/**
 * Normalize color input to hex format
 * 
 * @param string $color The color to normalize
 * @return string Normalized hex color
 */
function normalizeColor($color) {
    if (empty($color)) {
        return '#555';
    }
    
    // Already a hex color with #
    if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $color)) {
        return $color;
    }
    
    // Hex color without #
    if (preg_match('/^[0-9a-f]{3}([0-9a-f]{3})?$/i', $color)) {
        return '#' . $color;
    }
    
    // Check for named color
    $lowerColor = strtolower($color);
    if (isset(NAMED_COLORS[$lowerColor])) {
        return NAMED_COLORS[$lowerColor];
    }
    
    // Default fallback
    return '#555';
}

/**
 * Estimate text width for SVG rendering with better space handling
 * 
 * @param string $text The text to measure
 * @param int $fontSize Font size in pixels
 * @return float Estimated width in pixels
 */
function measureText($text, $fontSize = DEFAULT_FONT_SIZE) {
    // Use UTF-8 safe string length
    $length = mb_strlen($text, 'UTF-8');
    
    // Count character types for better width estimation
    $wideChars = preg_match_all('/[mwMWQG@]/', $text, $matches);
    $narrowChars = preg_match_all('/[ilI1!|\'\.]/', $text, $matches);
    $spaces = substr_count($text, ' ');
    
    // Adjust length based on character types
    // Spaces are narrower than average chars
    $adjustedLength = $length + ($wideChars * 0.4) - ($narrowChars * 0.3) - ($spaces * 0.2);
    
    return max(20, $adjustedLength * $fontSize * CHAR_WIDTH_RATIO);
}

/**
 * Generate the SVG badge with proper text spacing
 * 
 * @param string $label Left side text
 * @param string $message Right side text
 * @param string $color1 Left side background color
 * @param string $color2 Right side background color
 * @param string $fontColor Text color
 * @return string SVG XML content
 */
function generateBadgeSVG($label, $message, $color1, $color2, $fontColor) {
    // Calculate dimensions with better padding
    $labelWidth = ceil(measureText($label) + PADDING * 2);
    $messageWidth = ceil(measureText($message) + PADDING * 2);
    
    // Ensure minimum widths for readability
    $labelWidth = max(40, $labelWidth);
    $messageWidth = max(40, $messageWidth);
    $totalWidth = $labelWidth + $messageWidth;
    
    // Calculate text positions (scaled by 10 for SVG coordinate system)
    $labelX = ($labelWidth / 2) * 10;
    $messageX = ($labelWidth + $messageWidth / 2) * 10;
    
    // Calculate text lengths for SVG rendering with proper spacing
    $labelTextLength = max(100, ($labelWidth - PADDING * 1.5) * 10);
    $messageTextLength = max(100, ($messageWidth - PADDING * 1.5) * 10);
    
    // Escape text for XML/SVG while preserving spaces
    $escapedLabel = htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $escapedMessage = htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    
    // Validate colors
    $color1 = htmlspecialchars($color1, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $color2 = htmlspecialchars($color2, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $fontColor = htmlspecialchars($fontColor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    
    // Generate SVG with xsukax branding
    return '<?xml version="1.0" encoding="UTF-8"?>
<!-- Generated by xsukax Dynamic SVG Badge Generator v2.4 -->
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
     width="' . $totalWidth . '" height="' . BADGE_HEIGHT . '" 
     role="img" aria-label="' . $escapedLabel . ': ' . $escapedMessage . '">
    <title>' . $escapedLabel . ': ' . $escapedMessage . '</title>
    <defs>
        <linearGradient id="smooth" x2="0" y2="100%">
            <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
            <stop offset="1" stop-opacity=".1"/>
        </linearGradient>
        <clipPath id="round">
            <rect width="' . $totalWidth . '" height="' . BADGE_HEIGHT . '" rx="3" fill="#fff"/>
        </clipPath>
    </defs>
    <g clip-path="url(#round)">
        <rect width="' . $labelWidth . '" height="' . BADGE_HEIGHT . '" fill="' . $color1 . '"/>
        <rect x="' . $labelWidth . '" width="' . $messageWidth . '" height="' . BADGE_HEIGHT . '" fill="' . $color2 . '"/>
        <rect width="' . $totalWidth . '" height="' . BADGE_HEIGHT . '" fill="url(#smooth)"/>
    </g>
    <g fill="' . $fontColor . '" text-anchor="middle" 
       font-family="Verdana,Geneva,DejaVu Sans,sans-serif" 
       text-rendering="geometricPrecision" font-size="110"
       letter-spacing="0">
        <!-- Shadow for label -->
        <text aria-hidden="true" x="' . $labelX . '" y="150" fill="#010101" fill-opacity=".3" 
              transform="scale(.1)" textLength="' . $labelTextLength . '"
              lengthAdjust="spacing">' . $escapedLabel . '</text>
        <!-- Label text -->
        <text x="' . $labelX . '" y="140" transform="scale(.1)" 
              fill="' . $fontColor . '" textLength="' . $labelTextLength . '"
              lengthAdjust="spacing">' . $escapedLabel . '</text>
        <!-- Shadow for message -->
        <text aria-hidden="true" x="' . $messageX . '" y="150" fill="#010101" fill-opacity=".3" 
              transform="scale(.1)" textLength="' . $messageTextLength . '"
              lengthAdjust="spacing">' . $escapedMessage . '</text>
        <!-- Message text -->
        <text x="' . $messageX . '" y="140" transform="scale(.1)" 
              fill="' . $fontColor . '" textLength="' . $messageTextLength . '"
              lengthAdjust="spacing">' . $escapedMessage . '</text>
    </g>
</svg>';
}

/**
 * Process and validate input
 * 
 * @param string $input The raw input string
 * @return string Processed and validated input
 */
function processInput($input) {
    // Handle the fact that $_GET automatically converts + to spaces in query parameters
    // We need to get the raw query string to preserve + signs
    $rawQueryString = $_SERVER['QUERY_STRING'] ?? '';
    
    // Extract the badge parameter value from raw query string
    if (preg_match('/(?:^|&)badge=([^&]*)/', $rawQueryString, $matches)) {
        $rawBadgeValue = $matches[1];
        // Only decode %XX sequences, not + signs
        $decoded = rawurldecode($rawBadgeValue);
    } else {
        // Fallback to the passed input if we can't extract from query string
        $decoded = rawurldecode($input);
    }
    
    // Now sanitize the decoded input
    // Allow: letters, numbers, underscores, hyphens, hash, dots, spaces, plus signs
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-#\.\s\+]/', '', $decoded);
    
    // Limit input length to prevent DoS
    if (strlen($sanitized) > 300) {
        $sanitized = substr($sanitized, 0, 300);
    }
    
    return $sanitized;
}

// Main execution
try {
    // Get badge parameter - processInput will handle extracting from raw query string
    $defaultBadge = 'xsukax_Badge-Dynamic_Generator-555-4c1-fff.svg';
    $rawBadge = isset($_GET['badge']) ? $_GET['badge'] : $defaultBadge;
    
    // Process input: Extract from raw query string to preserve + signs, then sanitize
    $badge = processInput($rawBadge);
    
    // Parse parameters
    $params = parseUrlSegments($badge);
    
    // Generate and output SVG
    echo generateBadgeSVG(
        $params['label'],
        $params['message'],
        $params['color1'],
        $params['color2'],
        $params['fontColor']
    );
    
} catch (Exception $e) {
    // Error fallback - generate a default error badge
    echo generateBadgeSVG('xsukax', 'Error Occurred', '#e05d44', '#e05d44', '#fff');
    error_log('xsukax Badge Generator Error: ' . $e->getMessage());
}
?>