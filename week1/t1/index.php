<?php
$allowedColors = ['red', 'green', 'blue'];
$allowedSizes = ['small', 'medium', 'large'];

$fontOptions = [
    'bold' => 'Bold',
    'italic' => 'Italic',
];

$selectedColor = 'red';
$selectedSize = 'medium';
$selectedFonts = [];
$loremText = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedColor = $_POST['color'] ?? $selectedColor;
    $postedSize = $_POST['size'] ?? $selectedSize;
    $postedFonts = $_POST['font_style'] ?? [];

    if (in_array($postedColor, $allowedColors, true)) {
        $selectedColor = $postedColor;
    }

    if (in_array($postedSize, $allowedSizes, true)) {
        $selectedSize = $postedSize;
    }

    if (is_array($postedFonts)) {
        foreach ($postedFonts as $font) {
            if (array_key_exists($font, $fontOptions)) {
                $selectedFonts[] = $font;
            }
        }
    }
}

$fontSizeMap = [
    'small' => '14px',
    'medium' => '20px',
    'large' => '28px',
];

$styleParts = [
    'color: ' . $selectedColor,
    'font-size: ' . $fontSizeMap[$selectedSize],
];

if (in_array('bold', $selectedFonts, true)) {
    $styleParts[] = 'font-weight: bold';
}

if (in_array('italic', $selectedFonts, true)) {
    $styleParts[] = 'font-style: italic';
}

$inlineStyle = implode('; ', $styleParts);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 1 - Form Styling</title>
</head>

<body>
    <h1>Assignment 1</h1>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        <fieldset>
            <legend>Color</legend>
            <?php foreach ($allowedColors as $color): ?>
                <label>
                    <input
                        type="radio"
                        name="color"
                        value="<?= htmlspecialchars($color) ?>"
                        <?= $selectedColor === $color ? 'checked' : '' ?>>
                    <?= ucfirst(htmlspecialchars($color)) ?>
                </label>
                <br>
            <?php endforeach; ?>
        </fieldset>

        <br>

        <label for="size">Size:</label>
        <select name="size" id="size">
            <?php foreach ($allowedSizes as $size): ?>
                <option
                    value="<?= htmlspecialchars($size) ?>"
                    <?= $selectedSize === $size ? 'selected' : '' ?>>
                    <?= ucfirst(htmlspecialchars($size)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>

        <fieldset>
            <legend>Font style</legend>
            <?php foreach ($fontOptions as $key => $label): ?>
                <label>
                    <input
                        type="checkbox"
                        name="font_style[]"
                        value="<?= htmlspecialchars($key) ?>"
                        <?= in_array($key, $selectedFonts, true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </label>
                <br>
            <?php endforeach; ?>
        </fieldset>

        <br>
        <button type="submit">Submit</button>
    </form>

    <h2>Preview</h2>
    <p style="<?= htmlspecialchars($inlineStyle) ?>">
        <?= htmlspecialchars($loremText) ?>
    </p>
</body>

</html>