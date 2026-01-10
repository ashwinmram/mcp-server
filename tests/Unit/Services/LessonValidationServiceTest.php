<?php

use App\Services\LessonValidationService;

test('validates generic content as valid', function () {
    $service = new LessonValidationService();
    $content = 'Always use type hints in PHP functions. This improves code readability and IDE support.';

    $result = $service->validateIsGeneric($content);

    expect($result['is_valid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('rejects content with project-specific paths', function () {
    $service = new LessonValidationService();
    $content = 'The file is located at /var/www/myproject/app/Models/User.php';

    $result = $service->validateIsGeneric($content);

    expect($result['is_valid'])->toBeFalse()
        ->and($result['errors'])->toContain('Content contains project-specific path (/var/www/...)');
});

test('rejects content with user-specific paths', function () {
    $service = new LessonValidationService();
    $content = 'Check the file in /home/username/projects/myapp/config/app.php';

    $result = $service->validateIsGeneric($content);

    expect($result['is_valid'])->toBeFalse()
        ->and($result['errors'])->toContain('Content contains user-specific path (/home/username/...)');
});

test('warns about development domain references', function () {
    $service = new LessonValidationService();
    $content = 'Access the API at https://myapp.local/api/endpoint';

    $result = $service->validateIsGeneric($content);

    expect($result['is_valid'])->toBeTrue()
        ->and($result['warnings'])->not->toBeEmpty()
        ->and($result['warnings'][0])->toContain('development domain');
});

test('warns about project-specific name references', function () {
    $service = new LessonValidationService();
    $content = 'The "my-project" application uses this pattern';

    $result = $service->validateIsGeneric($content);

    expect($result['is_valid'])->toBeTrue()
        ->and($result['warnings'])->not->toBeEmpty()
        ->and($result['warnings'][0])->toContain('project-specific name');
});

test('suggests generic improvements', function () {
    $service = new LessonValidationService();
    $content = 'The file at /var/www/myproject/app/Models/User.php should be checked';

    $suggestions = $service->suggestGenericImprovements($content);

    expect($suggestions)->toBeArray()
        ->and($suggestions)->not->toBeEmpty();
});
