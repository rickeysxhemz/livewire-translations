<?php

declare(strict_types=1);

namespace LivewireTranslations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use LivewireTranslations\Services\LanguageManager;

class LanguageController extends Controller
{
    public function __construct(
        private readonly LanguageManager $languageManager
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->languageManager->getAllLanguages()
        ]);
    }

    public function active(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->languageManager->getActiveLanguages()
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/'],
            'name' => ['required', 'string', 'max:255'],
            'native_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0']
        ]);

        try {
            $language = $this->languageManager->saveLanguage($validated);

            return response()->json([
                'success' => true,
                'message' => 'Language saved successfully',
                'data' => $language
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error creating language', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Language code already exists or database error occurred'
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Unexpected error creating language', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while saving the language'
            ], 500);
        }
    }

    public function toggle(string $languageCode): JsonResponse
    {
        // Validate language code format
        if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $languageCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid language code format'
            ], 400);
        }

        try {
            $result = $this->languageManager->toggleLanguage($languageCode);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Language not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Language status updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling language status', [
                'language_code' => $languageCode,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the language status'
            ], 500);
        }
    }

    public function destroy(string $languageCode): JsonResponse
    {
        // Validate language code format
        if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $languageCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid language code format'
            ], 400);
        }

        try {
            $result = $this->languageManager->deleteLanguage($languageCode);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Language not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Language deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting language', [
                'language_code' => $languageCode,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the language'
            ], 500);
        }
    }
}
