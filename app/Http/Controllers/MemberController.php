<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $members = Member::query()
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully',
            'data' => MemberResource::collection($members->getCollection())->resolve(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
                'last_page' => $members->lastPage(),
            ],
        ]);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = Member::create([
            ...$request->validated(),
            'status' => Member::STATUS_ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member created successfully',
            'data' => MemberResource::make($member)->resolve(),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $member = Member::find($id);

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member retrieved successfully',
            'data' => MemberResource::make($member)->resolve(),
        ]);
    }
}
