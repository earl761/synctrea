<?php

namespace App\Http\Controllers;

use App\Models\Blog\Post;
use App\Models\Blog\Category;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $featuredPosts = Post::with(['category', 'author'])
            ->published()
            ->featured()
            ->latest('published_at')
            ->take(6)
            ->get();

        $categories = Category::withCount('posts')
            ->whereHas('posts', function ($query) {
                $query->published();
            })
            ->orderBy('posts_count', 'desc')
            ->get();

        return view('blog.index', compact('featuredPosts', 'categories'));
    }

    public function category($slug)
    {
        $category = Category::where('slug', $slug)
            ->firstOrFail();

        $posts = Post::with(['category', 'author'])
            ->where('blog_category_id', $category->id)
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return view('blog.category', compact('category', 'posts'));
    }

    public function show($slug)
    {
        $post = Post::with(['category', 'author'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $relatedPosts = $post->getRelatedPosts();

        return view('blog.show', compact('post', 'relatedPosts'));
    }
}