<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    public function getBlogs(Request $request)
    {
        $userId = auth()->id();

        $query = Blog::with(['user'])
            ->where('user_id', $userId);


        // search blogs by title or description
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                    ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        // filter: latest
        if ($request->filter == "latest") {
            $query->orderBy('created_at', 'desc');
        }

        // filter: most liked
        if ($request->filter == "most_liked") {
            $query->withCount('likes')->orderBy('likes_count', 'desc');
        }

        $blogs = $query->paginate(10);
        $blogs->getCollection()->transform(function ($blog) {
            return new BlogResource($blog);
        });
        return $this->success('Blogs listed successfully', $blogs);
    }

    public function addBlogs(Request $request)
    {
        $reqData = $request->all();
        $validated = Validator::make(
            $reqData,
            [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'image'  => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048']
            ],
            [
                'title.required'       => 'Blog title is required.',
                'description.required' => 'Description is required.',
                'image.required'       => 'Image is required.',
                'image.image'          => 'Image must be an image file.',
                'image.mimes'          => 'Image must be JPEG, PNG or JPG.',
                'image.max'            => 'Image size must not exceed 2MB.',
            ]
        );

        if ($validated->fails()) {
            return $this->error('Validation failed', 422, $validated->errors());
        }

        if ($request->hasFile('image')) {
            $blogImage = $request->file('image');
            $ext = $blogImage->getClientOriginalExtension();
            $imageName = 'blog_' . time() .  '.' . $ext;
            $blogImage->storeAs('blogs', $imageName, 'public');
        }

        $blog = Blog::create([
            'title'       => $reqData['title'],
            'description' => $reqData['description'],
            'image'       => $imageName,
            'user_id'     => auth()->id(),
        ]);

        return $this->success('Blog added successfully', new BlogResource($blog));
    }

    public function updateBlogs(Request $request, $id)
    {
        try {
            $reqData = $request->all();
            $blog = Blog::find($id);

            if (!$blog) {
                return $this->error('Blog not found.', 404);
            }

            // validate data
            $validated = Validator::make(
                $reqData,
                [
                    'title' => ['required', 'string', 'max:255'],
                    'description' => ['required', 'string'],
                    'image'  => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048']
                ],
                [
                    'title.required'       => 'Blog title is required.',
                    'description.required' => 'Description is required.',
                    'image.image'          => 'Image must be an image file.',
                    'image.mimes'          => 'Image must be JPEG, PNG or JPG.',
                    'image.max'            => 'Image size must not exceed 2MB.',
                ]
            );

            if ($validated->fails()) {
                return $this->error('Validation failed', 422, $validated->errors());
            }

            $blog->title = $request->title;
            $blog->description = $request->description;

            if ($request->hasFile('image')) {
                $blogImage = $request->file('image');
                $ext = $blogImage->getClientOriginalExtension();
                $imageName = 'blog_' . time() . '.' . $ext;
                $blogImage->storeAs('blogs', $imageName, 'public');


                // Delete old image safely
                if ($blog->image && Storage::disk('public')->exists('blogs/' . $blog->image)) {
                    Storage::disk('public')->delete('blogs/' . $blog->image);
                }

                $blog->image = $imageName;
            }

            $blog->save();

            return $this->success('Blog updated successfully', new BlogResource($blog));
        } catch (\Exception $e) {
            return $this->error('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    public function deleteBlog($id)
    {
        $blog = Blog::findOrFail($id);

        if (!$blog) {
            return $this->error(
                message: 'Blog not found.',
                code: 404
            );
        }
        if ($blog->image && Storage::disk('public')->exists('blogs/' . $blog->image)) {
            Storage::disk('public')->delete('blogs/' . $blog->image);
        }
        $blog->delete();

        return $this->success(
            message: 'Blog deleted successfully.',
            data: [],
            code: 200
        );
    }

    public function likeBlogs(Request $request)
    {
        $reqData = $request->all();
        $userId = $reqData['user_id'] ?? auth()->id();
        $blogId = $reqData['blog_id'];

        $blog = Blog::find($blogId);

        if (!$blog) {
            return $this->error(
                message: 'Blog not found.',
                code: 404
            );
        }

        // Check if user already liked this blog
        $existing = Like::where('likeable_id', $blog->id)
            ->where('likeable_type', Blog::class)
            ->where('user_id', $userId)
            ->first();

        // unlike blog 
        if ($existing) {
            $existing->delete();

            return response()->json([
                'status' => true,
                'liked' => false,
                'message' => 'Blog unliked successfully.',
                'likes_count' => $blog->likes()->count(),
            ]);
        }

        // like blog
        Like::create([
            'user_id' => $userId,
            'likeable_id' => $blog->id,
            'likeable_type' => Blog::class,
        ]);

        return response()->json([
            'status' => true,
            'liked' => true,
            'message' => 'Blog liked successfully.',
            'likes_count' => $blog->likes()->count(),
        ]);
    }
}
