# Requirement test matrix

FR-1 … FR-34 traceability for the final verification run. Every row has an
automated test; the manual checks that cannot be simulated in PHPUnit are in
section 2.

**Result snapshot (Phase 9 final run):**

```text
php artisan test
Tests:  317 passed (1709 assertions)
```

The Phase 9 suite (`tests/Feature/PhaseNineTest.php`, 10 tests / 44 assertions)
adds the regression evidence for the final audit; the rows it strengthens are
marked *Phase 9* below.

| FR | Requirement (short) | Automated test(s) | Result |
| --- | --- | --- | --- |
| FR-1 | Register as teacher/student, log in, log out, land on the role dashboard | `Auth/RegistrationTest::test_a_teacher_can_register`, `test_a_student_can_register`, `Auth/AuthenticationTest::test_users_can_authenticate_using_the_login_screen`, `test_users_can_logout` | PASS |
| FR-2 | Role-based access and role dashboards | `RoleAccessTest::test_each_role_reaches_its_own_dashboard`, `test_a_student_cannot_open_the_admin_dashboard`, `DashboardTest::test_each_role_is_locked_out_of_the_other_dashboards` | PASS |
| FR-3 | Forgot / reset password with a branded e-mail | `Auth/PasswordResetTest::test_reset_password_link_can_be_requested`, `test_the_reset_email_uses_the_branded_template`, `test_password_can_be_reset_with_valid_token` | PASS |
| FR-4 | Edit profile: name, e-mail, photo, bio, subject, password, delete account | `ProfileTest::test_profile_information_can_be_updated`, `test_correct_password_must_be_provided_to_delete_account`, `PhaseThreeUiTest::test_a_user_can_upload_and_remove_a_profile_photo`, `test_the_profile_photo_is_validated_for_type_and_size` | PASS |
| FR-5 | Public profile with posts, followers and following lists | `FeedTest::test_a_public_profile_shows_the_user_posts_and_counts`, `test_the_follower_lists_are_paginated_and_reachable`, `PhaseThreeCTest::test_a_teacher_profile_has_posts_classes_and_about_tabs` | PASS |
| FR-6 | Blocked accounts cannot log in and are ejected mid-session | `Auth/BlockedUserTest::test_a_blocked_user_cannot_log_in`, `test_a_logged_in_user_is_logged_out_when_blocked`, `PhaseSevenTest::test_a_blocked_user_cannot_post_comment_enroll_or_join` | PASS |
| FR-7 | Create a post (280 chars, optional image) | `FeedTest::test_a_logged_in_user_can_publish_a_post`, `test_a_post_cannot_be_longer_than_280_characters`, `test_an_empty_post_is_rejected`, `test_a_post_image_is_stored_in_the_posts_directory`, `PhaseEightTest::test_uploaded_images_are_resized_re_encoded_and_renamed` | PASS |
| FR-8 | Edit / delete own posts, image cleanup | `FeedTest::test_the_author_can_edit_and_delete_their_own_post`, `test_a_user_cannot_edit_someone_elses_post`, `test_a_user_cannot_delete_someone_elses_post`, `test_an_administrator_can_delete_a_post_but_not_edit_it`, `test_replacing_a_post_image_deletes_the_old_file`, `test_deleting_a_post_removes_its_image_file` | PASS |
| FR-9 | Like / unlike without duplicates, rate limited | `FeedTest::test_a_like_can_be_toggled_and_never_duplicates`, `test_a_hidden_post_cannot_be_liked_by_other_users`, `test_post_creation_is_rate_limited_to_ten_per_minute`, `PhaseEightTest::test_every_expensive_write_route_has_a_throttle` | PASS |
| FR-10 | Comment on a post; delete own or admin; hidden comments excluded | `FeedTest::test_a_user_can_comment_and_only_delete_their_own_comment`, `test_only_the_comment_author_or_an_admin_can_delete_a_comment`, `test_hidden_comments_are_not_shown_or_counted`, `PhaseSevenTest::test_hidden_comments_disappear_from_the_post_and_its_counter` | PASS |
| FR-11 | Follow / unfollow, no self-follow, live follower counts, AJAX on every surface | `FeedTest::test_a_student_can_follow_and_unfollow_a_teacher`, `test_nobody_can_follow_themselves`, `test_a_student_can_follow_another_student`, `test_search_results_and_feed_suggestions_use_the_ajax_follow_button`, `DashboardTest::test_a_student_can_follow_and_unfollow_a_teacher` | PASS |
| FR-12 | Home feed: own + followed posts, visible only, 10 per page, newest first | `FeedTest::test_the_feed_shows_only_my_posts_and_the_posts_of_people_i_follow`, `test_the_feed_is_paginated_ten_per_page_and_shows_the_newest_first`, `test_the_feed_answers_json_for_the_load_more_button`, `PerformanceTest::test_the_feed_paginates_and_keeps_its_query_count_bounded` | PASS |
| FR-13 | Hashtags: parse, Unicode, lowercase, sync, XSS-safe links, hashtag page | `FeedTest::test_hashtags_are_parsed_and_synced_in_lower_case`, `test_hashtags_support_unicode_and_are_lower_cased_everywhere`, `test_editing_a_post_resyncs_and_removes_old_hashtags`, `test_hashtag_links_are_xss_safe`, `test_the_hashtag_page_lists_visible_posts_with_that_tag` | PASS |
| FR-14 | Search people / hashtags / posts, role badge, pagination, suggestions, escaped term | `SearchTest::test_users_are_found_by_name`, `test_users_are_found_by_subject`, `test_users_are_found_by_bio`, `test_people_results_show_a_role_badge_and_a_follow_button`, `test_people_results_are_paginated`, `test_hashtag_results_count_only_visible_posts`, `test_posts_are_found_by_their_body_text`, `test_the_suggestion_endpoint_returns_people_and_hashtags`, `test_the_suggestion_endpoint_hides_blocked_people`, `test_like_wildcards_in_the_term_are_escaped`, `PhaseEightTest::test_search_input_cannot_break_the_query` | PASS |
| FR-15 | Create a class: validation, future date, duration 15–240, unique room, status filter | `ClassTest::test_a_teacher_can_create_a_class_with_a_private_room`, `test_a_class_cannot_be_created_in_the_past`, `test_the_duration_must_be_between_fifteen_and_two_hundred_forty_minutes`, `test_room_names_are_unique_and_ignore_the_request`, `test_a_teacher_can_filter_their_classes_by_status` | PASS |
| FR-16 | Edit / delete a class (owner only) | `ClassTest::test_the_owner_can_update_their_class`, `test_a_teacher_cannot_edit_another_teachers_class`, `test_students_and_admins_cannot_manage_classes` | PASS |
| FR-17 | Browse, search/filter classes, enrol / unenrol | `ClassTest::test_a_student_can_enroll_and_unenroll`, `test_a_student_can_search_and_filter_the_class_list`, `test_a_student_can_search_by_subject_and_filter_by_teacher`, `test_a_student_cannot_enroll_in_a_finished_class`, `test_only_students_can_enroll` | PASS |
| FR-18 | Class detail page with enrol / join actions | `ClassTest::test_the_room_page_embeds_jitsi_with_the_user_details`, `SmokeTest::test_student_can_open_their_pages_and_join_a_live_class` | PASS |
| FR-19 | Class search by title / subject / teacher, subject and teacher filters, private room name | `ClassTest::test_a_student_can_search_and_filter_the_class_list`, `test_a_student_can_search_by_subject_and_filter_by_teacher`, `test_room_names_are_unique_and_ignore_the_request` | PASS |
| FR-20 | Join the Jitsi room only as owning teacher or enrolled student, only while live, friendly not-started page, hangup flash | `ClassTest::test_only_the_teacher_and_enrolled_students_can_open_the_room`, `test_an_enrolled_student_can_join_only_when_the_class_is_live`, `test_a_hidden_class_cannot_be_joined`, `test_leaving_the_room_redirects_back_with_a_flash_message` | PASS |
| FR-21 | Status workflow scheduled → live → completed, legal transitions, auto-close, timing accessor | `ClassTest::test_the_teacher_controls_the_class_status`, `test_an_invalid_status_transition_is_refused`, `test_only_the_scheduled_live_completed_chain_is_allowed`, `test_a_live_class_is_completed_once_its_end_time_has_passed`, `test_the_timing_helper_describes_when_a_class_happens` | PASS |
| FR-22 | In-room experience: Jitsi embed, chat, moderator flag, subject/avatar, escaped display name | `ClassTest::test_the_room_page_embeds_jitsi_with_the_user_details`, `PhaseEightTest::test_the_jitsi_room_relaxes_framing_for_the_video_domain` | PASS |
| FR-23 | Notifications: new follower, like, comment, new class, class starting soon, no self, payload, dedupe | `NotificationTest::test_following_a_teacher_notifies_the_teacher`, `test_a_notification_stores_its_type_message_link_and_actor`, `test_liking_a_post_notifies_its_author`, `test_liking_your_own_post_does_not_notify_you`, `test_commenting_on_a_post_notifies_its_author`, `test_commenting_on_your_own_post_does_not_notify_you`, `test_creating_a_class_notifies_the_followers_of_the_teacher`, `test_creating_a_class_does_not_notify_the_teacher_themselves`, `test_a_blocked_follower_is_not_notified_of_a_new_class`, `test_the_starting_soon_command_notifies_the_teacher_and_the_students_once`, `test_the_starting_soon_command_skips_students_who_are_not_enrolled` | PASS |
| FR-24 | Notification bell, unread counter, hidden at zero, 30 s polling, filters | `NotificationTest::test_the_poll_endpoint_returns_the_unread_counter_and_the_latest_entries`, `test_the_bell_and_the_notifications_page_show_the_unread_count`, `test_the_unread_badge_is_hidden_when_there_is_nothing_to_read`, `test_the_poll_endpoint_requires_a_logged_in_user`, `test_the_notifications_page_can_be_filtered_to_unread_only` | PASS |
| FR-25 | Mark one (AJAX) / all as read, ownership, delete, open target | `NotificationTest::test_a_user_can_mark_one_notification_as_read`, `test_a_user_can_mark_all_notifications_as_read`, `test_marking_one_notification_as_read_answers_json_for_the_ajax_button`, `test_marking_all_as_read_only_touches_my_notifications`, `test_a_user_can_delete_their_own_notification`, `test_a_user_cannot_delete_somebody_elses_notification`, `test_opening_a_notification_marks_it_read_and_redirects_to_its_page`, `test_a_user_cannot_read_somebody_elses_notification` | PASS |
| FR-26 | Admin analytics: totals, charts, trends, recent activity | `DashboardTest::test_the_admin_dashboard_shows_the_platform_totals`, `test_the_admin_chart_counts_new_users_per_month`, `DashboardRedesignTest::test_the_admin_dashboard_renders_the_new_charts_panels_and_moderation_shortcuts` | PASS |
| FR-27 | User moderation: search, filter, block / unblock / delete | `AdminManagementTest::test_admins_can_search_and_filter_users`, `test_an_admin_can_block_and_unblock_a_user`, `test_an_admin_cannot_block_or_delete_themselves_or_another_admin`, `test_deleting_a_user_removes_their_content_and_reports` | PASS |
| FR-28 | Content moderation: hide / unhide / delete posts, classes and comments | `AdminManagementTest::test_an_admin_can_hide_unhide_and_delete_a_post`, `test_an_admin_can_hide_unhide_and_delete_a_class`, `PhaseSevenTest::test_hidden_posts_disappear_from_feed_profile_hashtag_and_search`, `ReportTest::test_hiding_a_comment_removes_it_from_the_post_page` | PASS |
| FR-29 | Teacher dashboard and enrolled-student roster | `PhaseThreeUiTest::test_the_teacher_dashboard_has_class_actions_and_enrollment_activity`, `ClassTest::test_the_teacher_sees_the_enrolled_students`, `ClassTest::test_the_dashboards_offer_working_join_buttons_and_upcoming_counts`, `DashboardTest::test_the_teacher_dashboard_only_counts_my_own_content` | PASS |
| FR-30 | Student dashboard, upcoming classes and teacher suggestions | `DashboardTest::test_the_student_dashboard_shows_upcoming_classes_and_teacher_suggestions`, `DashboardRedesignTest::test_the_student_dashboard_has_the_hero_recommendations_chart_and_feed_preview`, `ClassTest::test_the_dashboards_offer_working_join_buttons_and_upcoming_counts`, `ClassTest::test_a_student_sees_the_classes_they_enrolled_in` | PASS |
| FR-31 | Charts, theme toggle, sidebar state, design tokens | `DashboardRedesignTest::test_the_layout_ships_the_theme_toggle_and_the_design_tokens`, `test_the_dashboard_chart_endpoints_are_role_protected`, `PhaseThreeUiTest::test_the_layout_remembers_the_sidebar_state_and_has_a_script_stack` | PASS |
| FR-32 | Report a post or comment, one report per user per item | `ReportTest::test_a_user_can_report_somebody_elses_post`, `test_a_user_can_report_a_comment`, `test_the_same_user_cannot_report_the_same_item_twice`, `test_hidden_content_cannot_be_reported` | PASS |
| FR-33 | Moderation queue: filters, grouping, statuses, content actions | `ReportTest::test_the_queue_can_be_filtered_by_status_and_type`, `test_a_report_can_be_dismissed_or_marked_reviewed`, `PhaseSevenTest::test_the_report_queue_search_matches_the_offending_author`, `test_admin_lists_show_the_author_and_the_report_count` | PASS |
| FR-34 | Resolve reports: hide / unhide / delete content, block author, reviewed / dismissed | `ReportTest::test_hiding_a_post_marks_the_report_reviewed_and_removes_it_from_the_feed`, `test_blocking_the_author_marks_the_report_reviewed_and_logs_them_out`, `test_deleting_the_content_removes_the_content_and_its_reports`, `PhaseSevenTest::test_dismissing_a_report_keeps_the_content_visible` | PASS |

---

## Cross-cutting Phase 8 checks

| Check | Test | Result |
| --- | --- | --- |
| Security headers on every response, Jitsi exception | `PhaseEightTest::test_security_headers_are_sent_on_every_page`, `test_the_jitsi_room_relaxes_framing_for_the_video_domain` | PASS |
| Meta description, canonical, OG/Twitter, favicon, theme colour | `PhaseEightTest::test_the_layout_ships_meta_social_and_favicon_tags` (also asserts the ICO is non-empty and valid) | PASS |
| `@csrf` on every state-changing form (static scan) | `PhaseEightTest::test_every_post_form_carries_a_csrf_token` | PASS |
| Form Request validation (comment, class status, delete account) | `PhaseEightTest` comment/status/account tests | PASS |
| Mass-assignment safety (`user_id`, `is_hidden`, `role`, `is_blocked`) | `PhaseEightTest::test_write_requests_cannot_smuggle_protected_columns` | PASS |
| Upload rejection, decode/re-encode, resize, random name | `PhaseEightTest` upload tests | PASS |
| Rate limits: login, register, comment, report, route audit | `PhaseEightTest` rate-limit tests | PASS |
| Escaped output and safe search | `PhaseEightTest::test_user_content_is_escaped_in_the_feed`, `test_search_input_cannot_break_the_query` | PASS |
| Friendly 403 / 404 / 419 / 500 / 503 pages | `PhaseEightTest::test_all_friendly_error_pages_render` | PASS |
| Dashboard cache keys and five-minute TTL | `PhaseEightTest::test_dashboard_metrics_are_cached_for_five_minutes` | PASS |
| Query budgets and hot-path indexes | `PerformanceTest`, `PhaseEightTest::test_dashboards_stay_within_a_query_budget`, `test_the_hot_query_indexes_are_present` | PASS |
| Primary pages < 3 seconds | `PhaseEightTest::test_the_primary_pages_load_in_under_three_seconds` | PASS |

---

## Manual checks (environment-dependent)

These cannot be proven by the SQLite test suite; record the date and result when
performing them on staging.

| Check | How | Status |
| --- | --- | --- |
| Responsive layout at 360 / 768 / 1200 px | Browser devtools on landing, feed, class catalogue, dashboard and profile | documented manual check (Phase 3C + Phase 8) |
| Real SMTP password-reset delivery | Deploy with `MAIL_MAILER=smtp`, request a reset, confirm the branded e-mail arrives | documented manual check; unit test proves the template and notification |
| Real Jitsi call (camera/microphone) | Join a live class as teacher and enrolled student over HTTPS | documented manual check; header/embed tests cover the server side |
| Production queue worker | `QUEUE_CONNECTION=database`, run `queue:work`, trigger a notification | documented manual check; supervisor recipe in `docs/DEPLOYMENT.md` |
| MySQL 8 migrations and backup/restore | `php artisan migrate --force`, then restore a dump into a scratch database | documented manual check; MySQL unavailable in the build environment |
| Dark-mode contrast | Toggle theme at 360/1200 px, check text, buttons, alerts and charts | documented manual check (design tokens in `resources/sass`) |

The automated suite is the source of truth for FR-1…FR-34; the manual table is
the deployment sign-off list.
