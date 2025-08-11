<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\DictionaryService;

class FavoriteController extends Controller
{
    protected $dictionaryService;

    public function __construct(DictionaryService $dictionaryService)
    {
        $this->dictionaryService = $dictionaryService;
    }

    /**
     * Display a listing of the favorites.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Get favorites for the authenticated user only
            $favorites = Favorite::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = [];

            foreach($favorites as $favorite) {
                $wordDetailsArray = $this->dictionaryService->searchWord($favorite->word);
                $wordDetailsArray[0]['id'] = $favorite->id;
                $wordDetailsArray[0]['note'] = $favorite->note;
                $wordDetailsArray[0]['created_at'] = $favorite->created_at;
                $wordDetailsArray[0]['updated_at'] = $favorite->updated_at;

                $data[] = $wordDetailsArray[0];
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Favorites fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to fetch favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for a word definition (same as HomeController)
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'word' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $word = $request->word;
            $data = $this->dictionaryService->searchWord($word);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search word',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created favorite in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'word' => 'required|string|max:255',
                'note' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Create the favorite record with the authenticated user's ID
            $favorite = Favorite::create([
                'word' => $request->word,
                'note' => $request->note,
                'user_id' => $userId
            ]);

            // Optionally fetch word details from dictionary API
            try {
                $wordDetails = $this->dictionaryService->searchWord($request->word);
                if (!empty($wordDetails) && isset($wordDetails[0]['status_code']) && $wordDetails[0]['status_code'] === 200) {
                    // You could store additional word details in a separate table or JSON column
                    // For now, we'll just return the favorite with word details
                    $favorite->word_details = $wordDetails[0];
                }
            } catch (\Exception $e) {
                // If dictionary API fails, we still save the favorite
                // Just log the error
                \Log::warning('Failed to fetch word details for favorite: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Favorite created successfully',
                'data' => $favorite
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified favorite in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Find the favorite and ensure it belongs to the authenticated user
            $favorite = Favorite::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Favorite not found or access denied'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'note' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $favorite->update($request->only(['note']));

            return response()->json([
                'success' => true,
                'message' => 'Favorite updated successfully',
                'data' => $favorite
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete the specified favorite from storage.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Find the favorite and ensure it belongs to the authenticated user
            $favorite = Favorite::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Favorite not found or access denied'
                ], 404);
            }

            $favorite->delete(); // This will now soft delete (set deleted_at timestamp)

            return response()->json([
                'success' => true,
                'message' => 'Favorite moved to trash successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all soft deleted favorites (trash).
     */
    public function trashed(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Get trashed favorites for the authenticated user only
            $trashedFavorites = Favorite::onlyTrashed()
                ->where('user_id', $userId)
                ->orderBy('deleted_at', 'desc')
                ->get();

            // Add word details for each trashed favorite
            foreach($trashedFavorites as $favorite) {
                $wordDetailsArray = $this->dictionaryService->searchWord($favorite->word);
                $favorite->word_details = !empty($wordDetailsArray) ? $wordDetailsArray[0] : null;
            }

            return response()->json([
                'success' => true,
                'data' => $trashedFavorites
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trashed favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted favorite.
     */
    public function restore(Request $request, $id): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Find the trashed favorite and ensure it belongs to the authenticated user
            $favorite = Favorite::onlyTrashed()
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trashed favorite not found or access denied'
                ], 404);
            }

            $favorite->restore();

            return response()->json([
                'success' => true,
                'message' => 'Favorite restored successfully',
                'data' => $favorite
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a soft deleted favorite.
     */
    public function forceDelete(Request $request, $id): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Find the trashed favorite and ensure it belongs to the authenticated user
            $favorite = Favorite::onlyTrashed()
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trashed favorite not found or access denied'
                ], 404);
            }

            $favorite->forceDelete(); // This will permanently delete the record

            return response()->json([
                'success' => true,
                'message' => 'Favorite permanently deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore all soft deleted favorites.
     */
    public function restoreAll(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Restore all trashed favorites for the authenticated user only
            $restoredCount = Favorite::onlyTrashed()
                ->where('user_id', $userId)
                ->restore();

            return response()->json([
                'success' => true,
                'message' => "Successfully restored {$restoredCount} favorites"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore all favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete all soft deleted favorites.
     */
    public function forceDeleteAll(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = $request->user()->id;
            
            // Count and permanently delete all trashed favorites for the authenticated user only
            $deletedCount = Favorite::onlyTrashed()
                ->where('user_id', $userId)
                ->count();
                
            Favorite::onlyTrashed()
                ->where('user_id', $userId)
                ->forceDelete();

            return response()->json([
                'success' => true,
                'message' => "Successfully permanently deleted {$deletedCount} favorites"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete all favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
