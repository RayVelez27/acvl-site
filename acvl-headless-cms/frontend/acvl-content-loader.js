/**
 * ACVL Content Loader
 *
 * Drop this script into your static HTML pages to dynamically load content
 * from the WordPress headless CMS.
 *
 * Usage:
 *   1. Set the WordPress URL below (or via a <meta> tag).
 *   2. Add data-acvl attributes to HTML elements you want populated.
 *   3. Include this script before </body>.
 *
 * ── Configuration ──────────────────────────────────────────
 *
 *   <meta name="acvl-api-url" content="https://your-wordpress-site.com">
 *   <meta name="acvl-api-key" content="optional-key">
 *
 * ── Data attributes ────────────────────────────────────────
 *
 *   data-acvl="members"          → Populates element with member company list
 *   data-acvl="sponsors"         → All sponsors (or data-acvl-tier="platinum")
 *   data-acvl="board"            → Board members
 *   data-acvl="committees"       → Committee chairs
 *   data-acvl="events"           → Events (data-acvl-status="upcoming")
 *   data-acvl="advisors"         → Advisors
 *   data-acvl="settings"         → Site settings (hero, contact, etc.)
 *   data-acvl="content"          → Page content block (data-acvl-slug="mission-statement")
 *
 *   data-acvl-field="hero.title" → Bind a specific settings field to innerText
 */

(function () {
    'use strict';

    // ── Config ───────────────────────────────────────────
    var meta = function (name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    };

    var API_BASE = (meta('acvl-api-url') || '').replace(/\/+$/, '') + '/wp-json/acvl/v1';
    var API_KEY  = meta('acvl-api-key') || '';

    if (!meta('acvl-api-url')) {
        console.warn('[ACVL] No <meta name="acvl-api-url"> found. Content loader disabled.');
        return;
    }

    // ── Helpers ──────────────────────────────────────────
    function buildUrl(path, params) {
        var url = API_BASE + '/' + path;
        var query = [];
        if (API_KEY) query.push('api_key=' + encodeURIComponent(API_KEY));
        if (params) {
            for (var k in params) {
                if (params[k]) query.push(k + '=' + encodeURIComponent(params[k]));
            }
        }
        return query.length ? url + '?' + query.join('&') : url;
    }

    function fetchJSON(url) {
        return fetch(url).then(function (r) {
            if (!r.ok) throw new Error('ACVL API ' + r.status);
            return r.json();
        });
    }

    function getNestedValue(obj, path) {
        return path.split('.').reduce(function (o, k) { return o && o[k]; }, obj);
    }

    // ── Renderers ────────────────────────────────────────

    var renderers = {

        members: function (el, data) {
            el.innerHTML = data.map(function (m) {
                var logo = m.logo
                    ? '<img src="' + m.logo + '" alt="' + m.name + '" style="max-height:50px;width:auto;max-width:100%;object-fit:contain;">'
                    : '<span style="font-weight:600;color:#333;font-size:14px;">' + m.name + '</span>';
                var inner = '<div style="background:#fff;border:1px solid #e0e0e0;border-radius:10px;padding:20px 15px;text-align:center;min-height:70px;display:flex;align-items:center;justify-content:center;">' + logo + '</div>';

                // If inside a swiper, wrap in swiper-slide
                if (el.classList.contains('swiper-wrapper')) {
                    return '<div class="swiper-slide">' + inner + '</div>';
                }
                return '<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">' + inner + '</div>';
            }).join('');
        },

        sponsors: function (el, data) {
            var tier = el.getAttribute('data-acvl-tier');
            var items = tier ? data.filter(function (s) { return s.tier === tier; }) : data;

            el.innerHTML = items.map(function (s) {
                var logo = s.logo
                    ? '<img src="' + s.logo + '" alt="' + s.name + '">'
                    : '<span>' + s.name + '</span>';
                var link = s.url ? '<a href="' + s.url + '" target="_blank" rel="noopener">' + logo + '</a>' : logo;
                return '<div class="swiper-slide">' + link + '</div>';
            }).join('');
        },

        board: function (el, data) {
            el.innerHTML = data.map(function (p) {
                var photo = p.photo ? '<img src="' + p.photo + '" alt="' + p.name + '" style="width:100%;border-radius:12px;">' : '';
                return '<div class="col-lg-3 col-md-4 col-sm-6 mb-4">'
                    + '<div style="text-align:center;">'
                    + photo
                    + '<h5 style="margin-top:15px;margin-bottom:5px;">' + p.name + '</h5>'
                    + (p.job_title ? '<p style="color:#666;margin-bottom:3px;">' + p.job_title + '</p>' : '')
                    + (p.company ? '<p style="color:#999;font-size:14px;">' + p.company + '</p>' : '')
                    + '</div></div>';
            }).join('');
        },

        committees: function (el, data) {
            el.innerHTML = data.map(function (c) {
                var photo = c.photo ? '<img src="' + c.photo + '" alt="' + c.name + '" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">' : '';
                return '<div class="col-lg-4 col-md-6 mb-4">'
                    + '<div style="background:#fff;border-radius:15px;padding:25px;box-shadow:0 5px 20px rgba(0,0,0,0.08);text-align:center;">'
                    + photo
                    + '<h5 style="margin-top:12px;margin-bottom:5px;">' + c.name + '</h5>'
                    + (c.committee_name ? '<p style="color:#010066;font-weight:600;">' + c.committee_name + '</p>' : '')
                    + (c.company ? '<p style="color:#666;font-size:14px;">' + c.company + '</p>' : '')
                    + '</div></div>';
            }).join('');
        },

        events: function (el, data) {
            el.innerHTML = data.map(function (e) {
                var dateStr = '';
                if (e.start_date) {
                    dateStr = new Date(e.start_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                    if (e.end_date && e.end_date !== e.start_date) {
                        dateStr += ' – ' + new Date(e.end_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                    }
                }
                var img = e.image ? '<img src="' + e.image + '" alt="' + e.title + '" style="width:100%;border-radius:12px 12px 0 0;">' : '';
                return '<div class="col-lg-4 col-md-6 mb-4">'
                    + '<div style="background:#fff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.08);overflow:hidden;">'
                    + img
                    + '<div style="padding:20px;">'
                    + '<h5>' + e.title + '</h5>'
                    + (dateStr ? '<p style="color:#010066;font-weight:500;"><i class="fal fa-calendar"></i> ' + dateStr + '</p>' : '')
                    + (e.location ? '<p style="color:#666;"><i class="fal fa-map-marker-alt"></i> ' + e.location + '</p>' : '')
                    + (e.url ? '<a href="' + e.url + '" class="rts-btn btn-primary" style="margin-top:10px;display:inline-block;" target="_blank">Learn More</a>' : '')
                    + '</div></div></div>';
            }).join('');
        },

        advisors: function (el, data) {
            el.innerHTML = data.map(function (a) {
                var photo = a.photo ? '<img src="' + a.photo + '" alt="' + a.name + '" style="width:100%;border-radius:12px;">' : '';
                return '<div class="col-lg-3 col-md-4 col-sm-6 mb-4">'
                    + '<div style="text-align:center;">'
                    + photo
                    + '<h5 style="margin-top:15px;margin-bottom:5px;">' + a.name + '</h5>'
                    + (a.job_title ? '<p style="color:#666;margin-bottom:3px;">' + a.job_title + '</p>' : '')
                    + (a.company ? '<p style="color:#999;font-size:14px;">' + a.company + '</p>' : '')
                    + '</div></div>';
            }).join('');
        },

        content: function (el, data) {
            el.innerHTML = data.content || '';
        },

        settings: function (el, data) {
            // Populate individual fields via data-acvl-field
            var fields = el.querySelectorAll('[data-acvl-field]');
            fields.forEach(function (f) {
                var val = getNestedValue(data, f.getAttribute('data-acvl-field'));
                if (val !== undefined && val !== null) {
                    if (f.tagName === 'A') {
                        f.setAttribute('href', val);
                        if (!f.textContent.trim()) f.textContent = val;
                    } else if (f.tagName === 'IMG') {
                        f.setAttribute('src', val);
                    } else {
                        f.innerHTML = val;
                    }
                }
            });
        }
    };

    // ── Boot ─────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        var elements = document.querySelectorAll('[data-acvl]');

        elements.forEach(function (el) {
            var type = el.getAttribute('data-acvl');
            var renderer = renderers[type];
            if (!renderer) return;

            var params = {};
            if (type === 'sponsors' && el.getAttribute('data-acvl-tier')) {
                params.tier = el.getAttribute('data-acvl-tier');
            }
            if (type === 'events' && el.getAttribute('data-acvl-status')) {
                params.status = el.getAttribute('data-acvl-status');
            }

            var path = type;
            if (type === 'content') {
                var slug = el.getAttribute('data-acvl-slug');
                path = slug ? 'content/' + slug : 'content';
            }

            fetchJSON(buildUrl(path, params))
                .then(function (data) { renderer(el, data); })
                .catch(function (err) { console.error('[ACVL] Error loading ' + type + ':', err); });
        });
    });

})();
