<?php
/**
 * Single Resource template
 * Place in theme root as: single-resource.php
 */
get_header();

global $post;
$post_id = get_the_ID();

$file_url      = get_post_meta($post_id, '_resource_file_url',      true);
$file_name     = get_post_meta($post_id, '_resource_file_name',     true);
$file_type     = get_post_meta($post_id, '_resource_file_type',     true);
$is_free       = get_post_meta($post_id, '_resource_is_free',       true) !== '0';
$download_count = (int) get_post_meta($post_id, '_resource_download_count', true);

$categories = get_the_terms($post_id, 'resource_category');
$is_pdf     = strtolower($file_type) === 'pdf';
$home_url   = get_home_url();
$user_logged_in = is_user_logged_in();

// Related resources
$related_args = array(
    'post_type'      => 'resource',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'post__not_in'   => array($post_id),
    'orderby'        => 'date',
    'order'          => 'DESC',
);
if (!is_wp_error($categories) && $categories) {
    $cat_slugs = array_map(fn($c) => $c->slug, $categories);
    $related_args['tax_query'] = array(
        array('taxonomy' => 'resource_category', 'field' => 'slug', 'terms' => $cat_slugs),
    );
}
$related_query = new WP_Query($related_args);
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Top Ad Banner -->
    <div class="max-w-5xl mx-auto px-4 pt-6">
        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'banner', 'label' => 'Advertisement']); ?>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-10 md:py-14">

        <!-- Breadcrumb -->
        <nav class="text-sm text-slate-500 mb-6 font-inter flex items-center gap-2">
            <a href="<?php echo home_url(); ?>" class="hover:text-[var(--primary-color)] transition">Home</a>
            <span>/</span>
            <a href="<?php echo get_post_type_archive_link('resource'); ?>" class="hover:text-[var(--primary-color)] transition">Resources</a>
            <span>/</span>
            <span class="text-slate-900 font-medium"><?php the_title(); ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- ── Left Column: Detail ─────────────────────────────────────── -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Title Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">

                    <!-- Categories -->
                    <?php if (!is_wp_error($categories) && $categories): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach ($categories as $cat): ?>
                        <span class="text-xs font-semibold uppercase tracking-wide px-3 py-1 bg-blue-50 text-blue-700 rounded-full font-inter">
                            <?php echo esc_html($cat->name); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Title -->
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-3 font-inter leading-tight">
                        <?php the_title(); ?>
                    </h1>

                    <!-- Meta Row -->
                    <div class="flex items-center gap-4 text-sm text-slate-500 font-inter mb-6">
                        <span><?php the_date('F j, Y'); ?></span>
                        <?php if ($download_count > 0): ?>
                        <span>·</span>
                        <span><?php echo $download_count; ?> download<?php echo $download_count !== 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                        <span>·</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold <?php echo $is_free ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?>">
                            <?php echo $is_free ? 'Free' : 'Members Only'; ?>
                        </span>
                    </div>

                    <!-- Excerpt / Description -->
                    <?php if (has_excerpt()): ?>
                    <p class="text-slate-600 text-base leading-relaxed font-inter mb-0">
                        <?php the_excerpt(); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- PDF Preview -->
                <?php if ($file_url && $is_pdf): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900 font-inter flex items-center gap-2">
                            <span class="text-red-500">📄</span> Document Preview
                        </h2>
                        <a href="<?php echo esc_url($file_url); ?>" target="_blank"
                            class="text-sm text-[var(--primary-color)] font-semibold hover:underline font-inter">
                            Open in new tab ↗
                        </a>
                    </div>
                    <div class="relative w-full" style="height: 600px;">
                        <iframe src="<?php echo esc_url($file_url); ?>#toolbar=1&view=FitH"
                            class="w-full h-full border-0"
                            title="<?php the_title_attribute(); ?>">
                            <p class="p-6 text-slate-600 font-inter">
                                Your browser does not support PDF preview.
                                <a href="<?php echo esc_url($file_url); ?>" class="text-[var(--primary-color)] font-semibold underline">Download the file instead.</a>
                            </p>
                        </iframe>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Full Content -->
                <?php if (have_posts()): while (have_posts()): the_post(); ?>
                <?php if (get_the_content()): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4 font-inter">About This Resource</h2>
                    <div class="prose prose-slate max-w-none font-inter text-slate-700 leading-relaxed">
                        <?php the_content(); ?>
                    </div>
                </div>
                <?php endif; endwhile; endif; ?>

            </div>

            <!-- ── Right Column: Sidebar ──────────────────────────────────── -->
            <div class="space-y-6">

                <!-- Download / Action Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sticky top-6"
                    x-data="resourceDownloader(<?php echo $post_id; ?>, '<?php echo esc_js($home_url); ?>', <?php echo $is_free ? 'true' : 'false'; ?>, <?php echo $user_logged_in ? 'true' : 'false'; ?>)">

                    <!-- File Info -->
                    <?php if ($file_url): ?>
                    <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200 mb-5">
                        <span class="text-3xl">
                            <?php
                            $icons = array('pdf'=>'📄','doc'=>'📝','docx'=>'📝','ppt'=>'📊','pptx'=>'📊','xls'=>'📈','xlsx'=>'📈','zip'=>'🗜️');
                            echo $icons[$file_type] ?? '📋';
                            ?>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900 font-inter truncate">
                                <?php echo esc_html($file_name ?: get_the_title()); ?>
                            </p>
                            <p class="text-xs text-slate-500 font-inter uppercase"><?php echo esc_html(strtoupper($file_type)); ?> Document</p>
                        </div>
                    </div>

                    <!-- Download Button -->
                    <?php if ($is_free || $user_logged_in): ?>
                    <button @click="download()"
                        :disabled="downloading"
                        class="w-full py-3 bg-[var(--primary-color)] text-white font-semibold rounded-lg hover:opacity-90 transition flex items-center justify-center gap-2 font-inter disabled:opacity-50">
                        <svg x-show="!downloading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <svg x-show="downloading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span x-text="downloading ? 'Preparing...' : 'Download <?php echo strtoupper(esc_js($file_type)); ?>'"></span>
                    </button>

                    <!-- Success message -->
                    <p x-show="downloaded" class="text-center text-sm text-green-600 font-semibold mt-2 font-inter" x-cloak>
                        ✓ Download started!
                    </p>
                    <?php else: ?>
                    <!-- Login required -->
                    <div class="text-center">
                        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 font-inter">
                            🔒 Login required to download this resource.
                        </p>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>"
                            class="block w-full py-3 bg-[var(--primary-color)] text-white font-semibold rounded-lg hover:opacity-90 transition text-center font-inter">
                            Login to Download
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Open / View link -->
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank"
                        class="mt-3 block w-full py-2.5 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition text-center text-sm font-inter">
                        Open in Browser ↗
                    </a>
                    <?php else: ?>
                    <p class="text-slate-500 text-sm text-center font-inter">No file attached to this resource yet.</p>
                    <?php endif; ?>

                    <!-- Download counter -->
                    <?php if ($download_count > 0): ?>
                    <p class="text-center text-xs text-slate-400 mt-4 font-inter">
                        <?php echo $download_count; ?> people have downloaded this
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Related Resources -->
                <?php if ($related_query->have_posts()): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-900 mb-4 font-inter">Related Resources</h3>
                    <div class="space-y-4">
                        <?php while ($related_query->have_posts()): $related_query->the_post();
                            $r_file_type = get_post_meta(get_the_ID(), '_resource_file_type', true);
                            $r_icons = array('pdf'=>'📄','doc'=>'📝','docx'=>'📝','ppt'=>'📊','pptx'=>'📊');
                            $r_icon  = $r_icons[$r_file_type] ?? '📋';
                        ?>
                        <a href="<?php the_permalink(); ?>"
                            class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition group">
                            <span class="text-2xl flex-shrink-0"><?php echo $r_icon; ?></span>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900 group-hover:text-[var(--primary-color)] transition font-inter line-clamp-2"><?php the_title(); ?></h4>
                                <p class="text-xs text-slate-500 font-inter mt-0.5"><?php the_date('M j, Y'); ?></p>
                            </div>
                        </a>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function resourceDownloader(postId, homeUrl, isFree, isLoggedIn) {
    return {
        downloading: false,
        downloaded:  false,

        async download() {
            if (!isFree && !isLoggedIn) {
                window.location.href = '<?php echo wp_login_url(get_permalink()); ?>';
                return;
            }
            this.downloading = true;
            try {
                const res  = await fetch(`${homeUrl}/wp-json/property/v1/resource/${postId}/download`);
                const data = await res.json();
                if (data.success && data.file_url) {
                    const a = document.createElement('a');
                    a.href   = data.file_url;
                    a.target = '_blank';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    this.downloaded = true;
                } else {
                    alert(data.message || 'Unable to download.');
                }
            } catch(e) {
                alert('Download failed. Please try again.');
            }
            this.downloading = false;
        }
    };
}
</script>

<?php get_footer(); ?>
