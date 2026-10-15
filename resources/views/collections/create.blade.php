<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>New Collection</title>
</head>
<body style="font-family: system-ui; max-width: 900px; margin: 40px auto;">
  <h1>New Collection</h1>

  @if ($errors->any())
    <div style="color:#b00020; margin: 12px 0;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('admin.collections.store') }}">
    @csrf

    <label>Title *</label><br>
    <input name="title" value="{{ old('title') }}" style="width:100%; padding:10px; margin:6px 0 14px;"><br>

    <label>Subtitle</label><br>
    <input name="subtitle" value="{{ old('subtitle') }}" style="width:100%; padding:10px; margin:6px 0 14px;"><br>

    <label>Slug (optional)</label><br>
    <input name="slug" value="{{ old('slug') }}" placeholder="auto from title if empty"
           style="width:100%; padding:10px; margin:6px 0 14px;"><br>

    <label>Type *</label><br>
    <select name="type" style="width:100%; padding:10px; margin:6px 0 14px;">
      @foreach(['year','week','latest','topic','custom'] as $t)
        <option value="{{ $t }}" @selected(old('type','custom') === $t)>{{ $t }}</option>
      @endforeach
    </select>

    <div style="display:flex; gap:12px;">
      <div style="flex:1;">
        <label>Year</label><br>
        <input name="year" value="{{ old('year') }}" style="width:100%; padding:10px; margin:6px 0 14px;">
      </div>
      <div style="flex:1;">
        <label>Week Year</label><br>
        <input name="week_year" value="{{ old('week_year') }}" style="width:100%; padding:10px; margin:6px 0 14px;">
      </div>
      <div style="flex:1;">
        <label>Week No</label><br>
        <input name="week_no" value="{{ old('week_no') }}" style="width:100%; padding:10px; margin:6px 0 14px;">
      </div>
    </div>

    <label>Intro</label><br>
    <textarea name="intro" rows="5" style="width:100%; padding:10px; margin:6px 0 14px;">{{ old('intro') }}</textarea>

    <h3>SEO</h3>
    <label>SEO Title</label><br>
    <input name="seo_title" value="{{ old('seo_title') }}" style="width:100%; padding:10px; margin:6px 0 14px;"><br>

    <label>SEO Description</label><br>
    <textarea name="seo_description" rows="3" style="width:100%; padding:10px; margin:6px 0 14px;">{{ old('seo_description') }}</textarea>

    <label>SEO Keywords (comma separated)</label><br>
    <input name="seo_keywords" value="{{ old('seo_keywords') }}" style="width:100%; padding:10px; margin:6px 0 14px;"><br>

    <label>Hero Image URL (optional)</label><br>
    <input name="hero_image" value="{{ old('hero_image') }}" style="width:100%; padding:10px; margin:6px 0 14px;"><br>

    <label style="display:flex; gap:10px; align-items:center;">
      <input type="checkbox" name="is_published" value="1" @checked(old('is_published'))>
      Publish now
    </label>

    <div style="margin-top:16px;">
      <button type="submit" style="padding:10px 14px;">Create</button>
      <a href="{{ route('admin.collections.index') }}" style="margin-left:10px;">Cancel</a>
    </div>
  </form>
</body>
</html>
