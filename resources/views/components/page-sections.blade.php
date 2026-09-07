@props(['page', 'brand'])
@foreach ($page->activeSections as $section)
    @switch($section->type)
        @case('hero')
            <x-sections.hero :section="$section" :page="$page" />
        @break

        @case('text')
            <x-sections.text :section="$section" />
        @break

        @case('image_text')
            <x-sections.image-text :section="$section" />
        @break

        @case('services')
            <x-sections.content-cards :section="$section" :brand="$brand" kind="services" />
        @break

        @case('courses')
            <x-sections.content-cards :section="$section" :brand="$brand" kind="courses" />
        @break

        @case('gallery')
            <x-sections.gallery :section="$section" />
        @break

        @case('cta')
            <x-sections.cta :section="$section" />
        @break
    @endswitch
@endforeach
