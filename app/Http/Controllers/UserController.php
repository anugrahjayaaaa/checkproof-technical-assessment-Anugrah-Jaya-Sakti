<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}


    /**
     * Display a listing of the resource.
     */
    public function index(IndexUserRequest $request)
    {
        $data = $request->validated();

        $search = $data['search'] ?? null;
        $page = $data['page'] ?? 1;
        $sortBy = $data['sortBy'] ?? 'created_at';

        $sortDirection = ($sortBy === 'created_at') ? 'desc' : 'asc';

        $query = User::query()
            ->where('active', true)
            ->withCount('orders');

        if (filled($search)) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(
                perPage: 10,
                page: $page,
            );

        return response()->json([
            'page' => $users->currentPage(),
            'users' => UserCollection::collection($users->items()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create(
            $request->validated()
        );

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
