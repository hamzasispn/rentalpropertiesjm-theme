<?php
/**
 * Global amenity catalog — admin-defined groups + amenities that show as
 * checkboxes on the property add/edit form.
 *
 * Storage: single wp_options row `property_theme_amenity_catalog` holding
 *   [
 *     ['title' => 'Interior', 'amenities' => [
 *       ['title' => 'Wi-Fi', 'icon' => '/wp-content/uploads/wifi.svg'],
 *       ['title' => 'Air conditioning', 'icon' => ''],
 *     ]],
 *     ...
 *   ]
 *
 * On the front-end form, the catalog renders as grouped checkboxes. The user
 * still has the existing "add your own" field for one-off amenities the admin
 * didn't list. Both flow into the same `amenities_groups[…]` POST shape the
 * handler already expects — no backend schema change.
 */

if (!defined('ABSPATH')) exit;

const PROPERTY_THEME_AMENITY_OPTION = 'property_theme_amenity_catalog';

/**
 * Return the admin-defined catalog (always an array).
 */
function property_theme_get_amenity_catalog() {
    $raw = get_option(PROPERTY_THEME_AMENITY_OPTION, array());
    if (!is_array($raw)) return array();

    // Normalise each group/amenity so downstream code can trust the shape.
    $catalog = array();
    foreach ($raw as $group) {
        if (!is_array($group) || empty($group['title'])) continue;
        $amenities = array();
        if (!empty($group['amenities']) && is_array($group['amenities'])) {
            foreach ($group['amenities'] as $a) {
                if (!is_array($a) || empty($a['title'])) continue;
                $amenities[] = array(
                    'title' => sanitize_text_field($a['title']),
                    'icon'  => isset($a['icon']) ? esc_url_raw($a['icon']) : '',
                );
            }
        }
        $catalog[] = array(
            'title'     => sanitize_text_field($group['title']),
            'amenities' => $amenities,
        );
    }
    return $catalog;
}

/**
 * Admin menu — Properties → Amenity Catalog.
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=property',
        'Amenity Catalog',
        'Amenity Catalog',
        'manage_options',
        'property-amenity-catalog',
        'property_theme_amenity_catalog_page'
    );
});

/**
 * Admin page renderer + save handler.
 */
function property_theme_amenity_catalog_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    // Save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['property_theme_amenity_nonce'])
        && wp_verify_nonce($_POST['property_theme_amenity_nonce'], 'save_amenity_catalog')) {

        $groups_in = isset($_POST['groups']) && is_array($_POST['groups']) ? $_POST['groups'] : array();
        $clean     = array();
        foreach ($groups_in as $g) {
            if (empty($g['title'])) continue;
            $amenities = array();
            if (!empty($g['amenities']) && is_array($g['amenities'])) {
                foreach ($g['amenities'] as $a) {
                    if (empty($a['title'])) continue;
                    $amenities[] = array(
                        'title' => sanitize_text_field($a['title']),
                        'icon'  => esc_url_raw($a['icon'] ?? ''),
                    );
                }
            }
            $clean[] = array(
                'title'     => sanitize_text_field($g['title']),
                'amenities' => $amenities,
            );
        }
        update_option(PROPERTY_THEME_AMENITY_OPTION, $clean, false);
        echo '<div class="notice notice-success is-dismissible"><p>Amenity catalog saved.</p></div>';
    }

    $catalog = property_theme_get_amenity_catalog();
    ?>
    <div class="wrap">
        <h1>Amenity Catalog</h1>
        <p>Define the amenity groups (e.g. "Interior", "Outdoor", "Building") and the amenities in each. These appear as checkboxes on the add-property form — users just tick what applies. They can still add their own custom amenities on top of this list.</p>

        <form method="post" id="amenity-catalog-form">
            <?php wp_nonce_field('save_amenity_catalog', 'property_theme_amenity_nonce'); ?>

            <div id="amenity-groups" style="display:flex;flex-direction:column;gap:16px;margin-top:20px;">
                <?php if (empty($catalog)): ?>
                    <!-- Blank starter group so admin sees the shape -->
                    <?php $catalog = array(array('title' => '', 'amenities' => array(array('title' => '', 'icon' => '')))); ?>
                <?php endif; ?>

                <?php foreach ($catalog as $gi => $group): ?>
                    <div class="group-row" style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px;">
                        <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                            <input type="text"
                                name="groups[<?= $gi; ?>][title]"
                                value="<?= esc_attr($group['title']); ?>"
                                placeholder="Group title (e.g. Interior)"
                                style="flex:1;font-size:15px;font-weight:600;padding:6px 10px;" required>
                            <button type="button" class="button button-link-delete remove-group">Remove group</button>
                        </div>

                        <div class="amenities-list" data-gi="<?= $gi; ?>" style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ($group['amenities'] as $ai => $amenity): ?>
                                <div class="amenity-row" style="display:flex;gap:8px;align-items:center;">
                                    <input type="text"
                                        name="groups[<?= $gi; ?>][amenities][<?= $ai; ?>][title]"
                                        value="<?= esc_attr($amenity['title']); ?>"
                                        placeholder="Amenity title (e.g. Wi-Fi)"
                                        style="flex:1;padding:5px 8px;">
                                    <input type="text"
                                        name="groups[<?= $gi; ?>][amenities][<?= $ai; ?>][icon]"
                                        value="<?= esc_attr($amenity['icon']); ?>"
                                        placeholder="Icon URL (optional)"
                                        style="flex:1;padding:5px 8px;">
                                    <button type="button" class="button button-link-delete remove-amenity">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="button add-amenity" style="margin-top:10px;">+ Add amenity</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <p style="margin-top:16px;">
                <button type="button" class="button" id="add-group">+ Add group</button>
            </p>

            <?php submit_button('Save Catalog'); ?>
        </form>
    </div>

    <script>
    (function () {
        const container = document.getElementById('amenity-groups');
        // Renumbering avoids index gaps after deletes.
        const renumber = () => {
            container.querySelectorAll('.group-row').forEach((groupEl, gi) => {
                groupEl.querySelectorAll('input').forEach(inp => {
                    inp.name = inp.name.replace(/groups\[\d+\]/, `groups[${gi}]`);
                });
                groupEl.querySelector('.amenities-list').dataset.gi = gi;
                groupEl.querySelectorAll('.amenity-row').forEach((row, ai) => {
                    row.querySelectorAll('input').forEach(inp => {
                        inp.name = inp.name.replace(/\[amenities\]\[\d+\]/, `[amenities][${ai}]`);
                    });
                });
            });
        };

        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-amenity')) {
                const list = e.target.previousElementSibling; // .amenities-list
                const gi = list.dataset.gi;
                const ai = list.querySelectorAll('.amenity-row').length;
                const row = document.createElement('div');
                row.className = 'amenity-row';
                row.style.cssText = 'display:flex;gap:8px;align-items:center;';
                row.innerHTML =
                    `<input type="text" name="groups[${gi}][amenities][${ai}][title]" placeholder="Amenity title" style="flex:1;padding:5px 8px;">` +
                    `<input type="text" name="groups[${gi}][amenities][${ai}][icon]"  placeholder="Icon URL (optional)" style="flex:1;padding:5px 8px;">` +
                    `<button type="button" class="button button-link-delete remove-amenity">×</button>`;
                list.appendChild(row);
            } else if (e.target.classList.contains('remove-amenity')) {
                e.target.closest('.amenity-row').remove();
                renumber();
            } else if (e.target.classList.contains('remove-group')) {
                if (container.querySelectorAll('.group-row').length <= 1) return;
                e.target.closest('.group-row').remove();
                renumber();
            }
        });

        document.getElementById('add-group').addEventListener('click', () => {
            const gi = container.querySelectorAll('.group-row').length;
            const wrap = document.createElement('div');
            wrap.className = 'group-row';
            wrap.style.cssText = 'background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px;';
            wrap.innerHTML =
                `<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">` +
                    `<input type="text" name="groups[${gi}][title]" placeholder="Group title" style="flex:1;font-size:15px;font-weight:600;padding:6px 10px;" required>` +
                    `<button type="button" class="button button-link-delete remove-group">Remove group</button>` +
                `</div>` +
                `<div class="amenities-list" data-gi="${gi}" style="display:flex;flex-direction:column;gap:8px;">` +
                    `<div class="amenity-row" style="display:flex;gap:8px;align-items:center;">` +
                        `<input type="text" name="groups[${gi}][amenities][0][title]" placeholder="Amenity title" style="flex:1;padding:5px 8px;">` +
                        `<input type="text" name="groups[${gi}][amenities][0][icon]"  placeholder="Icon URL (optional)" style="flex:1;padding:5px 8px;">` +
                        `<button type="button" class="button button-link-delete remove-amenity">×</button>` +
                    `</div>` +
                `</div>` +
                `<button type="button" class="button add-amenity" style="margin-top:10px;">+ Add amenity</button>`;
            container.appendChild(wrap);
        });
    })();
    </script>
    <?php
}
