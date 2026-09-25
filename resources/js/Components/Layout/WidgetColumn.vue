<script setup>
import HomePostList from '@/Components/Newsletter/HomePostList.vue'
import CourseCatalog from '@/Components/Home/CourseCatalog.vue'
import FeaturedCourses from '@/Components/FeaturedCourses.vue'
import SocialLinks from '@/Components/SocialLinks.vue'
import BlogArticles from '@/Components/BlogArticles.vue'
import SectionHeader from '@/Components/SectionHeader.vue'

// Renders one homepage column from an admin-ordered widget list (002 US23).
// Both columns and both pages (homepage + /blog/{slug}) go through here, so
// widget order and visibility have exactly one implementation.
defineProps({
  // 'main' (homepage only) or 'side' (homepage + blog article pages)
  area: { type: String, default: 'side' },
  // Ordered, already-visibility-filtered descriptors from the server
  widgets: { type: Array, default: () => [] },
  // Shared data bag the built-in widgets draw their props from
  payload: { type: Object, default: () => ({}) },
})

// Built-in key → component + the props it takes from the shared payload.
// Adding a built-in means one entry here and one in
// HomepageWidgetService::BUILTIN — nothing else knows the key exists.
const BUILTINS = {
  popular_posts: {
    component: HomePostList,
    props: p => ({ posts: p.popularPosts ?? [] }),
  },
  course_catalog: {
    component: CourseCatalog,
    props: p => ({
      courses: p.courses ?? [],
      contentCategories: p.contentCategories ?? [],
      isAdmin: !!p.isAdmin,
    }),
  },
  featured_courses: {
    component: FeaturedCourses,
    props: p => ({ courses: p.featuredCourses ?? [] }),
  },
  social: {
    component: SocialLinks,
    props: p => ({ links: p.socialLinks ?? [], profile: p.snsProfile ?? null }),
  },
  blog: {
    component: BlogArticles,
    props: p => ({ articles: p.blogArticles ?? [] }),
  },
}

// An unknown key renders nothing rather than throwing: a widget row can outlive
// the component it points at, and the homepage is the last page that may break.
const builtin = key => BUILTINS[key] ?? null
</script>

<template>
  <!-- The side column spaces its cards; the main column's blocks carry their
       own bottom margin, so it must not add a second gap on top of that. -->
  <component
    :is="area === 'side' ? 'aside' : 'div'"
    :class="area === 'side' ? 'space-y-6' : 'min-w-0'"
  >
    <template v-for="w in widgets" :key="w.id ?? w.key">
      <component
        v-if="w.type === 'builtin' && builtin(w.key)"
        :is="builtin(w.key).component"
        v-bind="builtin(w.key).props(payload)"
      />

      <!-- Custom HTML: admin-authored trusted input (002 FR-078), same licence
           as the sales-page Markdown. The overflow guard is not optional — the
           side column is 365px and a wide table would break every page it is
           on. v-html sets innerHTML, so any <script> in there never runs. -->
      <section
        v-else-if="w.type === 'html'"
        class="max-w-full overflow-hidden"
        :class="[
          area === 'main' ? 'mb-6' : '',
          w.title ? 'bg-white border border-gray-200 p-4' : '',
        ]"
      >
        <SectionHeader v-if="w.title" :title="w.title" />
        <div v-html="w.html"></div>
      </section>
    </template>
  </component>
</template>
