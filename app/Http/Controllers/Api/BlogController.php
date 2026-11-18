<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Traits\EnsureOwnerTrait;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use EnsureOwnerTrait;

    // B (Browse): tampilkan semua blog beserta informasi pemiliknya.
    public function browse(Request $request)
    {
        $blogs = Blog::query()
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(fn (Blog $blog) => [
                'id' => $blog->id,
                'title' => $blog->title,
                'content' => $blog->content,
                'date' => $blog->created_at?->diffForHumans(),
                'user' => [
                    'id' => $blog->user?->id,
                    'name' => $blog->user?->name,
                ],
            ]);

        return response()->json(['data' => $blogs]);
    }

    // R (Read): detail blog hanya dapat dibaca pemiliknya.
    public function read(int $id, Request $request)
    {
        $blog = Blog::query()->with('user:id,name')->findOrFail($id);
        $this->ensureOwner($request, $blog);

        return response()->json([
            'message' => 'Detail blog berhasil ditampilkan.',
            'data' => $blog,
        ]);
    }

    // E (Edit): ubah blog milik user yang sedang login.
    public function edit(int $id, Request $request)
    {
        $blog = Blog::query()->findOrFail($id);
        $this->ensureOwner($request, $blog);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($data === []) {
            return response()->json([
                'message' => 'Minimal satu field (title atau content) harus diisi.',
            ], 422);
        }

        $blog->update($data);

        return response()->json([
            'message' => 'Blog berhasil diubah.',
            'data' => $blog->fresh('user:id,name'),
        ]);
    }

    // A (Add): user_id selalu berasal dari user yang terautentikasi.
    public function add(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string'],
        ]);

        $blog = $request->user()->blogs()->create($data);

        return response()->json([
            'message' => 'Blog berhasil ditambahkan.',
            'data' => $blog->load('user:id,name'),
        ], 201);
    }

    // D (Delete): hapus blog milik user yang sedang login.
    public function delete(int $id, Request $request)
    {
        $blog = Blog::query()->findOrFail($id);
        $this->ensureOwner($request, $blog);
        $blog->delete();

        return response()->json([
            'message' => 'Blog berhasil dihapus.',
            'data' => ['id' => $id],
        ]);
    }
}
