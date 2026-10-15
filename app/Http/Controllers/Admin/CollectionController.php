<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::orderByDesc('id')->paginate(20);
        return view('admin.collections.index', compact('collections'));
    }

    public function create()
    {
        return view('admin.collections.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'subtitle' => ['nullable','string','max:255'],
            'slug' => ['nullable','string','max:140'],
            'type' => ['required','in:year,week,latest,topic,custom'],
            'year' => ['nullable','integer','min:1970','max:2100'],
            'week_year' => ['nullable','integer','min:1970','max:2100'],
            'week_no' => ['nullable','integer','min:1','max:53'],
            'intro' => ['nullable','string'],
            'seo_title' => ['nullable','string','max:255'],
            'seo_description' => ['nullable','string','max:320'],
            'seo_keywords' => ['nullable','string','max:500'],
            'hero_image' => ['nullable','string','max:255'],
            'is_published' => ['nullable'],
        ]);

        // auto slug
        $slug = $data['slug'] ?? '';
        $slug = trim($slug) !== '' ? Str::slug($slug) : Str::slug($data['title']);

        // ensure unique slug (simple)
        $base = $slug;
        $i = 2;
        while (Collection::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }
        $data['slug'] = $slug;

        // publish handling
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? now() : null;

        Collection::create($data);

        return redirect()->route('admin.collections.index')->with('ok', 'Collection created');
    }

    public function edit(Collection $collection)
{
    return view('admin.collections.edit', compact('collection'));
}

public function update(Request $request, Collection $collection)
{
    $data = $request->validate([
        'title' => ['required','string','max:255'],
        'subtitle' => ['nullable','string','max:255'],
        'slug' => ['nullable','string','max:140'],
        'type' => ['required','in:year,week,latest,topic,custom'],
        'year' => ['nullable','integer','min:1970','max:2100'],
        'week_year' => ['nullable','integer','min:1970','max:2100'],
        'week_no' => ['nullable','integer','min:1','max:53'],
        'intro' => ['nullable','string'],
        'seo_title' => ['nullable','string','max:255'],
        'seo_description' => ['nullable','string','max:320'],
        'seo_keywords' => ['nullable','string','max:500'],
        'hero_image' => ['nullable','string','max:255'],
        'is_published' => ['nullable'],
    ]);

    // slug
    $slug = trim($data['slug'] ?? '') !== '' ? \Illuminate\Support\Str::slug($data['slug']) : \Illuminate\Support\Str::slug($data['title']);
    $base = $slug;
    $i = 2;
    while (Collection::where('slug', $slug)->where('id', '!=', $collection->id)->exists()) {
        $slug = $base.'-'.$i++;
    }
    $data['slug'] = $slug;

    // publish handling
    $data['is_published'] = $request->boolean('is_published');

    if ($data['is_published'] && ! $collection->published_at) {
        $data['published_at'] = now();
    }
    if (! $data['is_published']) {
        $data['published_at'] = null;
    }

    $collection->update($data);

    return redirect()->route('admin.collections.index')->with('ok', 'Collection updated');
}

    public function destroy(Collection $collection)
    {
        $collection->delete();
        return redirect()->route('admin.collections.index')->with('ok', 'Collection deleted');
    }

}
