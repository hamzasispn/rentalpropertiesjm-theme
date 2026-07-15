<?php
/**
 * Global amenity catalog — admin-defined groups + amenities that show as
 * inputs (checkbox / dropdown / text) on the property add/edit form.
 *
 * Storage schema (wp_option `property_theme_amenity_catalog`):
 *   [
 *     ['title' => 'Interior', 'amenities' => [
 *       ['title' => 'Wi-Fi',          'icon' => '', 'type' => 'checkbox', 'options' => []],
 *       ['title' => 'Flooring',       'icon' => '', 'type' => 'select',   'options' => ['Tile','Marble','Wood','Carpet']],
 *       ['title' => 'Ceiling Height', 'icon' => '', 'type' => 'text',     'options' => []],
 *     ]],
 *     ...
 *   ]
 *
 * Property meta (`_property_amenities_data`) mirrors this — each ticked/filled
 * amenity is saved with title + icon + value. `value=''` (empty string) means
 * a checkbox amenity that's simply present; selects and text carry the chosen
 * option / typed text. This is backward-compatible with pre-existing rows that
 * only had title + icon.
 */

if (!defined('ABSPATH')) exit;

const PROPERTY_THEME_AMENITY_OPTION = 'property_theme_amenity_catalog';

/**
 * Return the admin-defined catalog (always an array, always normalised).
 */
function property_theme_get_amenity_catalog() {
    $raw = get_option(PROPERTY_THEME_AMENITY_OPTION, array());
    if (!is_array($raw)) return array();

    $allowed_types = array('checkbox', 'select', 'text');
    $catalog = array();
    foreach ($raw as $group) {
        if (!is_array($group) || empty($group['title'])) continue;
        $amenities = array();
        if (!empty($group['amenities']) && is_array($group['amenities'])) {
            foreach ($group['amenities'] as $a) {
                if (!is_array($a) || empty($a['title'])) continue;
                $type    = isset($a['type']) && in_array($a['type'], $allowed_types, true) ? $a['type'] : 'checkbox';
                $options = array();
                if (isset($a['options'])) {
                    if (is_array($a['options'])) {
                        foreach ($a['options'] as $opt) {
                            $opt = trim((string) $opt);
                            if ($opt !== '') $options[] = sanitize_text_field($opt);
                        }
                    } elseif (is_string($a['options'])) {
                        // Legacy or admin-typed comma list
                        foreach (explode(',', $a['options']) as $opt) {
                            $opt = trim($opt);
                            if ($opt !== '') $options[] = sanitize_text_field($opt);
                        }
                    }
                }
                $amenities[] = array(
                    'title'   => sanitize_text_field($a['title']),
                    'icon'    => isset($a['icon']) ? esc_url_raw($a['icon']) : '',
                    'type'    => $type,
                    'options' => $options,
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
 * Default catalog — seeded from real-estate listing sites (zameen.com pattern,
 * adapted for Jamaica). Admin can load this from the button on the catalog page
 * as a starting point, then edit / add / remove to taste.
 */
function property_theme_default_amenity_catalog() {
    return array(
        array(
            'title' => 'Main Features',
            'amenities' => array(
                array('title' => 'Furnished',                'type' => 'select',   'options' => array('Yes', 'No', 'Semi-Furnished')),
                array('title' => 'Parking Spaces',           'type' => 'text',     'options' => array()),
                array('title' => 'Floors in Building',       'type' => 'text',     'options' => array()),
                array('title' => 'Elevators',                'type' => 'text',     'options' => array()),
                array('title' => 'Double Glazed Windows',    'type' => 'checkbox', 'options' => array()),
                array('title' => 'Central Air Conditioning', 'type' => 'checkbox', 'options' => array()),
                array('title' => 'Central Heating',          'type' => 'checkbox', 'options' => array()),
                array('title' => 'Flooring',                 'type' => 'select',   'options' => array('Tile', 'Marble', 'Wood', 'Laminate', 'Carpet', 'Concrete')),
                array('title' => 'Electricity Backup',       'type' => 'select',   'options' => array('None', 'UPS', 'Generator', 'Solar')),
                array('title' => 'Ceiling Height (ft)',      'type' => 'text',     'options' => array()),
                array('title' => 'Rooftop / Terrace',        'type' => 'checkbox', 'options' => array()),
                array('title' => 'Balcony',                  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Storage Room',             'type' => 'checkbox', 'options' => array()),
                array('title' => 'Waste Disposal',           'type' => 'checkbox', 'options' => array()),
            ),
        ),
        array(
            'title' => 'Rooms',
            'amenities' => array(
                array('title' => 'Kitchens',        'type' => 'text',     'options' => array()),
                array('title' => 'Dining Rooms',    'type' => 'text',     'options' => array()),
                array('title' => 'Drawing Rooms',   'type' => 'text',     'options' => array()),
                array('title' => 'Study Rooms',     'type' => 'text',     'options' => array()),
                array('title' => 'Powder Rooms',    'type' => 'text',     'options' => array()),
                array('title' => 'Laundry Room',    'type' => 'checkbox', 'options' => array()),
                array('title' => 'Servant Quarter', 'type' => 'checkbox', 'options' => array()),
                array('title' => 'Store Rooms',     'type' => 'text',     'options' => array()),
                array('title' => 'Prayer Room',     'type' => 'checkbox', 'options' => array()),
                array('title' => 'Gym',             'type' => 'checkbox', 'options' => array()),
                array('title' => 'Steam Room',      'type' => 'checkbox', 'options' => array()),
                array('title' => 'Home Theatre',    'type' => 'checkbox', 'options' => array()),
            ),
        ),
        array(
            'title' => 'Business & Communication',
            'amenities' => array(
                array('title' => 'Wi-Fi / Broadband Internet', 'type' => 'checkbox', 'options' => array()),
                array('title' => 'Cable TV',                   'type' => 'checkbox', 'options' => array()),
                array('title' => 'Satellite TV',               'type' => 'checkbox', 'options' => array()),
                array('title' => 'Intercom',                   'type' => 'checkbox', 'options' => array()),
                array('title' => 'Landline Phone',             'type' => 'checkbox', 'options' => array()),
            ),
        ),
        array(
            'title' => 'Community Features',
            'amenities' => array(
                array('title' => 'Community Lawn / Garden',   'type' => 'checkbox', 'options' => array()),
                array('title' => 'Community Swimming Pool',   'type' => 'checkbox', 'options' => array()),
                array('title' => 'Community Gym',             'type' => 'checkbox', 'options' => array()),
                array('title' => 'Clubhouse',                 'type' => 'checkbox', 'options' => array()),
                array('title' => 'Kids Play Area',            'type' => 'checkbox', 'options' => array()),
                array('title' => 'BBQ Area',                  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Community Centre',          'type' => 'checkbox', 'options' => array()),
                array('title' => 'First Aid / Medical Room',  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Day Care Centre',           'type' => 'checkbox', 'options' => array()),
            ),
        ),
        array(
            'title' => 'Health & Recreation',
            'amenities' => array(
                array('title' => 'Private Lawn / Garden',  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Private Swimming Pool',  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Jacuzzi',                'type' => 'checkbox', 'options' => array()),
                array('title' => 'Sauna',                  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Sports Court',           'type' => 'checkbox', 'options' => array()),
                array('title' => 'Sea / Ocean View',       'type' => 'checkbox', 'options' => array()),
                array('title' => 'Mountain View',          'type' => 'checkbox', 'options' => array()),
                array('title' => 'Beach Access',           'type' => 'checkbox', 'options' => array()),
            ),
        ),
        array(
            'title' => 'Security & Safety',
            'amenities' => array(
                array('title' => 'Gated Community',      'type' => 'checkbox', 'options' => array()),
                array('title' => '24/7 Security',        'type' => 'checkbox', 'options' => array()),
                array('title' => 'CCTV Cameras',         'type' => 'checkbox', 'options' => array()),
                array('title' => 'Security Staff',       'type' => 'checkbox', 'options' => array()),
                array('title' => 'Maintenance Staff',    'type' => 'checkbox', 'options' => array()),
                array('title' => 'Facilities for Disabled', 'type' => 'checkbox', 'options' => array()),
                array('title' => 'Smoke Detectors',      'type' => 'checkbox', 'options' => array()),
                array('title' => 'Fire Extinguishers',   'type' => 'checkbox', 'options' => array()),
            ),
        ),
        array(
            'title' => 'Nearby Locations',
            'amenities' => array(
                array('title' => 'Nearby Schools',         'type' => 'checkbox', 'options' => array()),
                array('title' => 'Nearby Hospitals',       'type' => 'checkbox', 'options' => array()),
                array('title' => 'Nearby Shopping Malls',  'type' => 'checkbox', 'options' => array()),
                array('title' => 'Nearby Restaurants',     'type' => 'checkbox', 'options' => array()),
                array('title' => 'Public Transport Nearby','type' => 'checkbox', 'options' => array()),
                array('title' => 'Distance to Airport (km)', 'type' => 'text',   'options' => array()),
                array('title' => 'Distance to Beach (km)', 'type' => 'text',     'options' => array()),
            ),
        ),
        array(
            'title' => 'Utilities',
            'amenities' => array(
                array('title' => 'Water Supply',    'type' => 'select',   'options' => array('Municipal', 'Well', 'Tank', 'Rainwater')),
                array('title' => 'Sewerage',        'type' => 'select',   'options' => array('Public', 'Septic Tank', 'Other')),
                array('title' => 'Gas Supply',      'type' => 'select',   'options' => array('Piped', 'Cylinder', 'None')),
                array('title' => 'Solar Panels',    'type' => 'checkbox', 'options' => array()),
                array('title' => 'Rainwater Harvesting', 'type' => 'checkbox', 'options' => array()),
            ),
        ),
    );
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
 * Admin page renderer + save/seed handler.
 */
function property_theme_amenity_catalog_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    // Seed / reset action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['load_defaults'])
        && !empty($_POST['property_theme_amenity_nonce'])
        && wp_verify_nonce($_POST['property_theme_amenity_nonce'], 'save_amenity_catalog')) {
        update_option(PROPERTY_THEME_AMENITY_OPTION, property_theme_default_amenity_catalog(), false);
        echo '<div class="notice notice-success is-dismissible"><p>Default amenity catalog loaded. Edit anything you like below.</p></div>';
    }
    // Save action
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['property_theme_amenity_nonce'])
        && wp_verify_nonce($_POST['property_theme_amenity_nonce'], 'save_amenity_catalog')) {

        $allowed_types = array('checkbox', 'select', 'text');
        $groups_in     = isset($_POST['groups']) && is_array($_POST['groups']) ? $_POST['groups'] : array();
        $clean         = array();
        foreach ($groups_in as $g) {
            if (empty($g['title'])) continue;
            $amenities = array();
            if (!empty($g['amenities']) && is_array($g['amenities'])) {
                foreach ($g['amenities'] as $a) {
                    if (empty($a['title'])) continue;
                    $type = isset($a['type']) && in_array($a['type'], $allowed_types, true) ? $a['type'] : 'checkbox';
                    $options = array();
                    if ($type === 'select' && !empty($a['options'])) {
                        // Textarea, one option per line
                        foreach (preg_split('/\r\n|\r|\n/', (string) $a['options']) as $opt) {
                            $opt = trim($opt);
                            if ($opt !== '') $options[] = sanitize_text_field($opt);
                        }
                    }
                    $amenities[] = array(
                        'title'   => sanitize_text_field($a['title']),
                        'icon'    => esc_url_raw($a['icon'] ?? ''),
                        'type'    => $type,
                        'options' => $options,
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
        <h1 style="display:flex;align-items:center;justify-content:space-between;">
            <span>Amenity Catalog</span>
            <?php if (empty($catalog)): ?>
                <form method="post" style="margin:0;">
                    <?php wp_nonce_field('save_amenity_catalog', 'property_theme_amenity_nonce'); ?>
                    <button type="submit" name="load_defaults" value="1" class="button button-primary"
                        onclick="return confirm('Load the default amenity catalog? (Kitchens, bedrooms, security, etc.)')">
                        Load default catalog
                    </button>
                </form>
            <?php else: ?>
                <form method="post" style="margin:0;">
                    <?php wp_nonce_field('save_amenity_catalog', 'property_theme_amenity_nonce'); ?>
                    <button type="submit" name="load_defaults" value="1" class="button"
                        onclick="return confirm('This OVERWRITES the current catalog with defaults. Continue?')">
                        Reset to default catalog
                    </button>
                </form>
            <?php endif; ?>
        </h1>

        <p>Define amenity groups and amenities. Each amenity has an <strong>input type</strong> — checkbox (yes/no), dropdown (pick one from a list), or text field (free text). These render on the add-property form so users just tick or fill.</p>

        <form method="post" id="amenity-catalog-form">
            <?php wp_nonce_field('save_amenity_catalog', 'property_theme_amenity_nonce'); ?>

            <div id="amenity-groups" style="display:flex;flex-direction:column;gap:16px;margin-top:20px;">
                <?php
                if (empty($catalog)) {
                    // Empty starter row so admin sees the shape.
                    $catalog = array(array('title' => '', 'amenities' => array(array('title' => '', 'icon' => '', 'type' => 'checkbox', 'options' => array()))));
                }
                foreach ($catalog as $gi => $group):
                ?>
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
                            <?php foreach ($group['amenities'] as $ai => $amenity):
                                $type = $amenity['type'] ?? 'checkbox';
                                $opts_str = (!empty($amenity['options']) && is_array($amenity['options']))
                                    ? implode("\n", $amenity['options'])
                                    : '';
                            ?>
                                <div class="amenity-row" style="display:grid;grid-template-columns:2fr 2fr 1.2fr 2fr auto;gap:8px;align-items:start;">
                                    <input type="text"
                                        name="groups[<?= $gi; ?>][amenities][<?= $ai; ?>][title]"
                                        value="<?= esc_attr($amenity['title']); ?>"
                                        placeholder="Amenity title (e.g. Wi-Fi)"
                                        style="padding:5px 8px;">
                                    <input type="text"
                                        name="groups[<?= $gi; ?>][amenities][<?= $ai; ?>][icon]"
                                        value="<?= esc_attr($amenity['icon'] ?? ''); ?>"
                                        placeholder="Icon URL (optional)"
                                        style="padding:5px 8px;">
                                    <select name="groups[<?= $gi; ?>][amenities][<?= $ai; ?>][type]"
                                        class="amenity-type-select" style="padding:5px 8px;">
                                        <option value="checkbox" <?php selected($type, 'checkbox'); ?>>Checkbox</option>
                                        <option value="select"   <?php selected($type, 'select'); ?>>Dropdown</option>
                                        <option value="text"     <?php selected($type, 'text'); ?>>Text field</option>
                                    </select>
                                    <textarea
                                        name="groups[<?= $gi; ?>][amenities][<?= $ai; ?>][options]"
                                        class="amenity-options"
                                        placeholder="Dropdown options (one per line)"
                                        rows="2"
                                        style="padding:5px 8px;<?= $type !== 'select' ? 'display:none;' : ''; ?>"><?= esc_textarea($opts_str); ?></textarea>
                                    <button type="button" class="button button-link-delete remove-amenity" style="align-self:center;">×</button>
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

        const renumber = () => {
            container.querySelectorAll('.group-row').forEach((groupEl, gi) => {
                groupEl.querySelectorAll('input, select, textarea').forEach(inp => {
                    inp.name = inp.name.replace(/groups\[\d+\]/, `groups[${gi}]`);
                });
                groupEl.querySelector('.amenities-list').dataset.gi = gi;
                groupEl.querySelectorAll('.amenity-row').forEach((row, ai) => {
                    row.querySelectorAll('input, select, textarea').forEach(inp => {
                        inp.name = inp.name.replace(/\[amenities\]\[\d+\]/, `[amenities][${ai}]`);
                    });
                });
            });
        };

        const amenityRowHtml = (gi, ai) =>
            `<input type="text" name="groups[${gi}][amenities][${ai}][title]" placeholder="Amenity title" style="padding:5px 8px;">` +
            `<input type="text" name="groups[${gi}][amenities][${ai}][icon]"  placeholder="Icon URL (optional)" style="padding:5px 8px;">` +
            `<select name="groups[${gi}][amenities][${ai}][type]" class="amenity-type-select" style="padding:5px 8px;">` +
                `<option value="checkbox">Checkbox</option>` +
                `<option value="select">Dropdown</option>` +
                `<option value="text">Text field</option>` +
            `</select>` +
            `<textarea name="groups[${gi}][amenities][${ai}][options]" class="amenity-options" placeholder="Dropdown options (one per line)" rows="2" style="padding:5px 8px;display:none;"></textarea>` +
            `<button type="button" class="button button-link-delete remove-amenity" style="align-self:center;">×</button>`;

        container.addEventListener('change', (e) => {
            if (e.target.classList && e.target.classList.contains('amenity-type-select')) {
                const row = e.target.closest('.amenity-row');
                const opts = row && row.querySelector('.amenity-options');
                if (opts) opts.style.display = (e.target.value === 'select') ? '' : 'none';
            }
        });

        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-amenity')) {
                const list = e.target.previousElementSibling; // .amenities-list
                const gi = list.dataset.gi;
                const ai = list.querySelectorAll('.amenity-row').length;
                const row = document.createElement('div');
                row.className = 'amenity-row';
                row.style.cssText = 'display:grid;grid-template-columns:2fr 2fr 1.2fr 2fr auto;gap:8px;align-items:start;';
                row.innerHTML = amenityRowHtml(gi, ai);
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
                    `<div class="amenity-row" style="display:grid;grid-template-columns:2fr 2fr 1.2fr 2fr auto;gap:8px;align-items:start;">` +
                        amenityRowHtml(gi, 0) +
                    `</div>` +
                `</div>` +
                `<button type="button" class="button add-amenity" style="margin-top:10px;">+ Add amenity</button>`;
            container.appendChild(wrap);
        });
    })();
    </script>
    <?php
}
