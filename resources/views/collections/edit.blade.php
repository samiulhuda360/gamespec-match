@extends('admin.layout')

@section('content')
<h1>Edit Collection</h1>

@if ($errors->any())
  <div style="padding:10px;border:1px solid #f00;margin-bottom:12px;">
    <ul style="margin:0;">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('admin.collections.update', $collection) }}">
  @csrf
  @method('PUT')

  <div style="margin-bottom:10px;">
    <label>Title *</label><br>
    <input type="text" name="title" value="{{ old('title', $collection->title) }}" style="width:420px;">
  </div>

  <div style="margin-bottom:10px;">
    <label>Subtitle</label><br>
    <input type="text" name="subtitle" value="{{ old('subtitle', $collection->subtitle) }}" style="width:420px;">
  </div>

  <div style="margin-bottom:10px;">
    <label>Slug (optional)</label><br>
    <input type="text" name="slug" value="{{ old('slug', $collection->slug) }}" style="width:420px;">
  </div>

  <div style="margin-bottom:10px;">
    <label>Type *</label><br>
    @php($t = old('type', $collection->type))
    <select name="type">
      <option value="custom" @selected($t==='custom')>custom</option>
      <option value="latest" @selected($t==='latest')>latest</option>
      <option value="week"   @selected($t==='week')>week</option>
      <option value="year"   @selected($t==='year')>year</option>
      <option value="topic"  @selected($t==='topic')>topic</option>
    </select>
  </div>

  <div style="margin-bottom:10px;">
    <label>Year</label><br>
    <input type="number" name="year" value="{{ old('year', $collection->year) }}" min="1970" max="2100">
  </div>

  <div style="margin-bottom:10px;">
    <label>Week Year</label><br>
    <input type="number" name="week_year" value="{{ old('week_year', $collection->week_year) }}" min="1970" max="2100">
  </div>

  <div style="margin-bottom:10px;">
    <label>Week No</label><br>
    <input type="number" name="week_no" value="{{ old('week_no', $collection->week_no) }}" min="1" max="53">
  </div>

  <div style="margin-bottom:10px;">
    <label>Intro</label><br>
    <textarea name="intro" rows="4" style="width:520px;">{{ old('intro', $collection->intro) }}</textarea>
  </div>

  <hr>

  <div style="margin-bottom:10px;">
    <label>SEO Title</label><br>
    <input type="text" name="seo_title" value="{{ old('seo_title', $collection->seo_title) }}" style="width:520px;">
  </div>

  <div style="margin-bottom:10px;">
    <label>SEO Description</label><br>
    <textarea name="seo_description" rows="3" style="width:520px;">{{ old('seo_description', $collection->seo_description) }}</textarea>
  </div>

  <div style="margin-bottom:10px;">
    <label>SEO Keywords</label><br>
    <input type="text" name="seo_keywords" value="{{ old('seo_keywords', $collection->seo_keywords) }}" style="width:520px;">
  </div>

  <div style="margin-bottom:10px;">
    <label>Hero Image</label><br>
    <input type="text" name="hero_image" value="{{ old('hero_image', $collection->hero_image) }}" style="width:520px;">
  </div>

  <div style="margin:12px 0;">
    <label>
      <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $collection->is_published))>
      Published
    </label>
  </div>

  <button type="submit">Save</button>
  <a href="{{ route('admin.collections.index') }}" style="margin-left:10px;">Back</a>
</form>
@endsection
