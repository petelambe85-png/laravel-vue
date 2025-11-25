<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Loan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoanAPIController extends Controller
{
    public function getIndex(?int $id = null)
    {
        if ($id) {
            return Loan::with('book', 'user')->find($id);
        }

        return Loan::with('book', 'user')->orderBy('id', 'DESC')->get();
    }

    public function postIndex(Request $request) {
        $data = $request->validate([
            'book_id'     => 'required|exists:books,id',
            'user_id'     => 'required|exists:users,id',
            'returned_at' => 'nullable|datetime',
        ]);

        return Loan::create([
            'book_id'     => $data['book_id'],
            'user_id'     => $data['user_id'],
            'loaned_at'   => now(),
            'returned_at' => $data['returned_at'] ?? null,
        ]);
    }

    public function putIndex(Request $request, int $id) {
        $loan = Loan::find($id);
        if (empty($loan)) {
            throw new Exception('Could not find loan.');
        }

        $data = $request->validate([
            'returned_at' => 'nullable|datetime',
        ]);

        $loan->update($data);
        return $loan;
    }

    public function deleteIndex(int $id) {
        $loan = Loan::find($id);
        if (empty($loan)) {
            throw new Exception('Could not find loan.');
        }

        $loan->delete();

        return response()->noContent();
    }

    public function putExtend(Request $request, Loan $loan): JsonResponse
    {
        if($loan->due_at && $loan->due_at->isPast()){
            return response()->json([
                'message'=>'Cannot extend an overdue loan.'
            ], 422);
        }

        if($loan->returned_at !== null) {
            return response() ->json([
                'message' => 'Cannot extend a returned loan.'
            ], 422
        );
        }

        $data = $request->validate([
            'additional_days' => 'required|integer|min:1|max:30',
        ]);

        $additionalDays = $data['additional_days'];

        $currentDueAt = $loan->due_at ?? $loan->loanded_at->copy()->addDays(14);

        $loan->due_at = $currentDueAt->copy()->addDays($additionalDays);
        $loan->save();

        return response()->json($loan->fresh(['user','book']));
    }

    public function topActiveUsers(): JsonResponse
    {
        $users = User::whereHas('loans', function($query){
            $query->whereNull('returned_at');
        })
        ->withCount(['loans as active_loans' => function($query){
            $query->whereNull('returned_at');
        }])
        ->orderByDesc('active_loans')
        ->limit(5)
        ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
