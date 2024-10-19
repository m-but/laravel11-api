<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index()
    {
        $post = Post::latest()->paginate(5);

        return new PostResource(true, "List Data Post", $post);
    }

    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            "image" => "required|image|mimes:jpg,jpeg,png,gif,svg|max:2048",
            "title" => "required",
            "content" => "required"
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //upload image
        $imagePath = $request->file("image")->store("posts", "public");

        //create post
        $post = Post::create([
            "image" => $imagePath,
            "title" => $request->title,
            "content" => $request->content
        ]);

        //return response
        return new PostResource(true, "Data post berhasil ditambahkan!", $post);
    }

    public function show($id)
    {
        $post = Post::find($id);

        return new PostResource(true, "Detail data post", $post);
    }

    public function update(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            "title" => "required",
            "content" => "required",
            "image" => "nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Find the post
        $post = Post::find($id);

        // Check if post exists
        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        // Update the post attributes
        $postData = [
            "title" => $request->title,
            "content" => $request->content,
        ];

        // Check if there is a new image to upload
        if ($request->hasFile("image")) {
            $oldImagePath = $post->image;

            // Upload image
            $imagePath = $request->file("image")->store("posts", "public");

            // Hapus gambar lama jika ada
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }

            // Update post with new image path
            $postData["image"] = $imagePath;
        }

        // Update post
        $post->update($postData);

        return new PostResource(true, "Post berhasil diubah!", $post);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $imagePath = $post->image;

        // Hapus dari database
        $post->delete();

        // Hapus file gambar dari Storage
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json(['success' => 'Image deleted successfully']);
    }
}
