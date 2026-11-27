<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlogRequest;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $lang = $this->language($request);
        $queryParams = $this->languageQuery($request, $lang);
        $blogs = Blog::with([
            'category', 'author',
            'translations' => fn ($query) => $query->where('locale', $lang),
        ])
            ->latest('dt_created')->orderByDesc('blog_id')
            ->paginate(6)->withQueryString()->appends($queryParams);

        return view('blog.index', compact('blogs', 'lang', 'queryParams'));
    }

    public function admin(Request $request): View
    {
        $lang = $this->language($request);
        $queryParams = $this->languageQuery($request, $lang);
        $blogs = Blog::with([
            'category', 'author',
            'translations' => fn ($query) => $query->where('locale', $lang),
        ])
            ->latest('dt_created')->orderByDesc('blog_id')
            ->paginate(10)->withQueryString()->appends($queryParams);

        return view('blog.admin', compact('blogs', 'lang', 'queryParams'));
    }

    public function create(): View
    {
        $blog = new Blog;
        $categories = Category::orderBy('category_name')->get();

        return view('blog.create', compact('blog', 'categories'));
    }

    public function store(BlogRequest $request): RedirectResponse
    {
        $blog = Blog::create($request->validated() + ['user_id' => null]);

        return redirect()->route('blog.show', $blog)->with('success', 'Новину створено.');
    }

    public function show(Request $request, Blog $blog): View
    {
        $lang = $this->language($request);
        $queryParams = $this->languageQuery($request, $lang);
        $blog->load([
            'category', 'author',
            'translations' => fn ($query) => $query->where('locale', $lang),
        ]);
        $content = $blog->localizedContent($lang);

        return view('blog.show', compact('blog', 'content', 'lang', 'queryParams'));
    }

    public function edit(Blog $blog): View
    {
        $categories = Category::orderBy('category_name')->get();

        return view('blog.edit', compact('blog', 'categories'));
    }

    public function update(BlogRequest $request, Blog $blog): RedirectResponse
    {
        $blog->update($request->validated());

        return redirect()->route('blog.show', $blog)->with('success', 'Новину оновлено.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $blog->delete();

        return redirect()->route('blog.index')->with('success', 'Новину видалено.');
    }

    private function language(Request $request): string
    {
        $lang = $request->query('lang', 'uk');

        if (! is_string($lang) || strlen($lang) > 10 || ! preg_match('/\A[a-z]{2,10}(?:[-_][a-z0-9]{2,8})?\z/i', $lang)) {
            return 'uk';
        }

        return strtolower($lang);
    }

    private function languageQuery(Request $request, string $lang): array
    {
        return array_replace($request->query(), $request->query->has('lang') ? ['lang' => $lang] : []);
    }
}
