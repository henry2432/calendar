jQuery(document).ready(function($) {
    
    // ============================================
    // REDESIGNED AUTH INTERFACE
    // ============================================
    
    // Tab Switching with Keyboard Navigation
    function initAuthInterface() {
        var $tabBtns = $('.kayarine-auth-tab-btn');
        var $panels = $('.kayarine-auth-panel');
        
        if ($tabBtns.length === 0) return;

        // Tab Click Handler
        $tabBtns.on('click', function(e) {
            e.preventDefault();
            var tabName = $(this).data('tab');
            switchAuthTab(tabName);
        });

        // Keyboard Navigation (Arrow keys)
        $tabBtns.on('keydown', function(e) {
            var $currentBtn = $(this);
            var $allBtns = $tabBtns;
            var currentIndex = $allBtns.index($currentBtn);
            var targetIndex = -1;

            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                targetIndex = (currentIndex + 1) % $allBtns.length;
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                targetIndex = (currentIndex - 1 + $allBtns.length) % $allBtns.length;
            } else if (e.key === 'Home') {
                e.preventDefault();
                targetIndex = 0;
            } else if (e.key === 'End') {
                e.preventDefault();
                targetIndex = $allBtns.length - 1;
            }

            if (targetIndex >= 0) {
                var targetTab = $allBtns.eq(targetIndex).data('tab');
                switchAuthTab(targetTab);
                $allBtns.eq(targetIndex).focus();
            }
        });

        // Auth Switch Buttons
        $('.kayarine-auth-switch-btn').on('click', function(e) {
            e.preventDefault();
            var targetTab = $(this).data('switch-to');
            switchAuthTab(targetTab);
        });
    }

    // Switch Tab Function
    function switchAuthTab(tabName) {
        var $panels = $('.kayarine-auth-panel');
        var $tabBtns = $('.kayarine-auth-tab-btn');

        // Remove active from all
        $panels.removeClass('active');
        $tabBtns.removeClass('active').attr('aria-selected', 'false');

        // Add active to selected
        $('#kayarine-' + tabName + '-panel').addClass('active');
        $('.kayarine-auth-tab-btn[data-tab="' + tabName + '"]')
            .addClass('active')
            .attr('aria-selected', 'true')
            .focus();

        // Auto-focus first input in the active panel
        $('#kayarine-' + tabName + '-panel').find('input:first').focus();
    }

    // Form Submission Enhancement
    function enhanceAuthForms() {
        $(document).on('submit', 'form.login, form.register-form', function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
            
            if ($submitBtn.length > 0 && !$form.data('submitting')) {
                $form.data('submitting', true);
                var originalText = $submitBtn.text();
                var originalHTML = $submitBtn.html();
                
                // Show loading state
                $submitBtn.prop('disabled', true);
                $submitBtn.html('<span class="spinner"></span> ' + ($submitBtn.attr('value') || '處理中...'));
                
                // Reset after timeout (error handling)
                var resetTimeout = setTimeout(function() {
                    if ($submitBtn.prop('disabled')) {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.html(originalHTML);
                        $form.data('submitting', false);
                    }
                }, 5000);

                // Clear timeout on successful submission
                $(window).one('beforeunload', function() {
                    clearTimeout(resetTimeout);
                });
            }
        });
    }

    // Input Focus Effects
    function enhanceInputs() {
        $(document).on('focus blur', '.kayarine-auth-panel-content input', function() {
            var $input = $(this);
            var $label = $input.prev('label');
            
            if ($input.is(':focus')) {
                $label.css('color', '#3182ce');
            } else {
                $label.css('color', '');
            }
        });

        // Real-time validation feedback
        $(document).on('change', '.kayarine-auth-panel-content input[type="email"]', function() {
            var $input = $(this);
            var email = $input.val().trim();
            var isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            
            if (email && !isValid) {
                $input.css('border-color', '#f56565');
            } else {
                $input.css('border-color', '');
            }
        });
    }

    // Initialize
    initAuthInterface();
    enhanceAuthForms();
    enhanceInputs();
    
    // ============================================
    // END REDESIGNED AUTH INTERFACE
    // ============================================
    
    // Check if we are on a booking product page (Single or Unified)
    var $wrapper = $('#kayarine-booking-fields');
    var $unifiedWrapper = $('#kayarine-unified-booking');

    if ($wrapper.length === 0 && $unifiedWrapper.length === 0) {
        return;
    }

    var productId = $wrapper.length > 0 ? $wrapper.data('product-id') : 0;
    var $priceDisplay = $('#kb-total-price');
    var $dateInput = $('#kayarine_booking_date');
    var $form = $('form.cart');
    
    // Config loaded from PHP
    var config = kayarine_config; 
    
    // ----------------------------------------------------
    // 1. Price Calculation Logic
    // ----------------------------------------------------
    function calculateTotal() {
        var dateVal = $dateInput.val();
        
        // Determine Price Type (Weekday/Weekend)
        var priceType = 'weekday';
        
        if (dateVal) {
            var dateObj = new Date(dateVal);
            var day = dateObj.getDay(); // 0 = Sun, 6 = Sat
            
            // Check Weekend
            if (day === 0 || day === 6) {
                priceType = 'weekend';
            }
            
            // Check Holidays
            if (config.holidays.includes(dateVal)) {
                priceType = 'weekend';
            }
        }
        
        if (!dateVal) {
            $priceDisplay.text('$ -');
            return;
        }

        var total = 0;

        // A. Main Product Price
        // We look for our custom main quantity input class .kb-val with specific ID or context
        var $mainQtyInput = $('#kb-main-qty-display');
        var mainQty = parseInt( $mainQtyInput.val() ) || 1;
        
        var mainPrice = 0;
        if (config.prices[productId]) {
            mainPrice = config.prices[productId][priceType];
        } else {
            console.warn('Price not found for product ID:', productId);
        }
        total += (mainPrice * mainQty);

        // B. Add-ons Price
        // We look for inputs that are Add-ons (have data-addon-id)
        $('.kb-val.kb-addon-qty').each(function() {
            var $input = $(this);
            var qty = parseInt($input.val()) || 0;
            var addonId = $input.data('addon-id');
            
            if (qty > 0 && config.prices[addonId]) {
                var addonPrice = config.prices[addonId][priceType];
                total += (addonPrice * qty);
            }
        });

        // Update Display
        $priceDisplay.text('$' + total);
    }

    // ----------------------------------------------------
    // 2. Events
    // ----------------------------------------------------
    
    // ----------------------------------------------------
    // 1.5. Flatpickr Initialization (Modern Date Picker)
    // ----------------------------------------------------
    
    // Helper: Prepare Disable Dates (Points + Ranges)
    var disableDates = config.blackout_dates ? [...config.blackout_dates] : [];
    if (config.blocked_ranges) {
        config.blocked_ranges.forEach(function(range) {
            disableDates.push({ from: range.from, to: range.to });
        });
    }

    // Helper: Format Date to YYYY-MM-DD
    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    // Helper: Day Class Logic (Weekends/Holidays)
    function dayCreateLogic(dObj, dStr, fp, dayElem) {
        var date = dayElem.dateObj;
        var day = date.getDay(); // 0=Sun, 6=Sat
        var formatted = formatDate(date);

        // Check Weekend (Sat/Sun)
        if (day === 0 || day === 6) {
            dayElem.classList.add('kb-date-weekend');
        }

        // Check Holiday
        if (config.holidays && config.holidays.includes(formatted)) {
            dayElem.classList.add('kb-date-holiday');
        }
    }

    if (typeof flatpickr !== 'undefined') {
        // Pre-calculate disabled/enabled dates to avoid expensive checks during rendering
        var preCalculatedDisabledDates = [];
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Optimize: Build disabled dates list once instead of checking during onDayCreate
        if (!(config.event_dates && config.event_dates[productId])) {
            // For blacklist mode: pre-calculate which dates are disabled
            // This avoids expensive .includes() checks during rendering
            if (config.blackout_dates) {
                preCalculatedDisabledDates = preCalculatedDisabledDates.concat(config.blackout_dates);
            }
        }
        
        var fpOptions = {
            dateFormat: "Y-m-d",
            minDate: "today",
            inline: true, // Inline Calendar
            locale: {
                firstDayOfWeek: 0 // Sunday start
            },
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                // Lightweight: Only add CSS classes, avoid expensive operations
                var date = dayElem.dateObj;
                var day = date.getDay(); // 0=Sun, 6=Sat
                
                // Check Weekend (Sat/Sun) - Simple & Fast
                if (day === 0 || day === 6) {
                    dayElem.classList.add('kb-date-weekend');
                }
                
                // Holiday check - only if needed (avoid .includes() for performance)
                // Pre-filter for current month to reduce checks
                if (config.holidays && config.holidays.length < 50) {
                    var formatted = formatDate(date);
                    if (config.holidays.includes(formatted)) {
                        dayElem.classList.add('kb-date-holiday');
                    }
                }
            },
            onChange: function(selectedDates, dateStr, instance) {
                calculateTotal();
            }
        };

        // Limited Time Event Logic (Whitelist vs Blacklist)
        if (config.event_dates && config.event_dates[productId]) {
            // This product has specific allowed dates (Whitelist)
            fpOptions.enable = config.event_dates[productId];
        } else {
            // Standard Product (Blacklist)
            fpOptions.disable = preCalculatedDisabledDates;
        }

        flatpickr("#kayarine_booking_date", fpOptions);
    }

    // Recalculate on any change (Single Product Page)
    $dateInput.on('change', calculateTotal);
    $('.kb-val').on('input change', calculateTotal);

    // ----------------------------------------------------
    // 1.8 Toggle Sections (Add-ons)
    // ----------------------------------------------------
    $(document).on('click', '.kb-toggle-header', function() {
        var targetId = $(this).data('target');
        var $content = $('#' + targetId);
        
        $content.slideToggle(200);
        $(this).toggleClass('collapsed');
    });

    // ----------------------------------------------------
    // 1.9 Gallery Interaction & Draggable Slider
    // ----------------------------------------------------
    
    // A. Main Image Slider Logic (Draggable + Click to Jump)
    var $slider = $('.product-image-slider');
    var $thumbs = $('.gallery-item');

    // Click Thumb -> Scroll Slider
    $thumbs.on('click', function() {
        var index = $(this).data('index');
        var slideWidth = $slider.width();
        
        $slider.animate({
            scrollLeft: index * slideWidth
        }, 300);

        // Update Active State
        $thumbs.removeClass('active');
        $(this).addClass('active');
    });

    // Handle Native Scroll Snap -> Update Thumb Active State
    // Using simple debounce to detect end of scroll
    var scrollTimer;
    $slider.on('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            var scrollLeft = $slider.scrollLeft();
            var width = $slider.width();
            var index = Math.round(scrollLeft / width);
            
            $thumbs.removeClass('active');
            $thumbs.eq(index).addClass('active');
        }, 100);
    });

    // B. Draggable Logic for Desktop (Mouse)
    // Touch is handled natively by CSS (scroll-snap + overflow-x)
    const slider = document.querySelector('.product-image-slider');
    let isDown = false;
    let startX;
    let scrollLeftVal;

    if(slider) {
        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('active'); // CSS cursor: grabbing
            startX = e.pageX - slider.offsetLeft;
            scrollLeftVal = slider.scrollLeft;
        });
        
        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.classList.remove('active');
        });
        
        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.classList.remove('active');
        });
        
        slider.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2; // Scroll speed multiplier
            slider.scrollLeft = scrollLeftVal - walk;
        });
    }

    // C. Show More Logic for Hidden Addons
    $('#kb-show-more-addons').click(function() {
        $('.kb-hidden-default').fadeIn().removeClass('kb-hidden-default');
        $(this).hide();
    });

    // Plus/Minus Button Logic (Generic for both Add-ons and Main Product)
    // New class selector: .kb-btn
    $(document).on('click', '.kb-btn', function(e) {
        e.preventDefault(); // Prevent default button behavior
        var $btn = $(this);
        // Find sibling input with class .kb-val
        var $input = $btn.siblings('.kb-val');
        
        if ($input.length === 0) {
             // Fallback search
             $input = $btn.parent().find('input');
        }
        
        var currentVal = parseInt($input.val()) || 0;
        var minVal = parseInt($input.attr('min')) || 0;
        
        if ($btn.hasClass('plus') || $btn.text().trim() === '+') {
            $input.val(currentVal + 1);
        } else {
            if (currentVal > minVal) {
                $input.val(currentVal - 1);
            }
        }
        $input.trigger('change');
    });

    // Initial calc (Single Product Page)
    calculateTotal();
    
    // ✅ 性能優化：延遲日期快取預加載（requestIdleCallback）
    // 原問題：無條件在 document.ready 時發送 2 個 AJAX，阻塞頁面渲染
    // 優化：在瀏覽器空閒時預加載（不阻塞主線程）
    (function() {
        var today = new Date();
        var tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        function formatDate2(date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            return [year, month, day].join('-');
        }
        
        var todayStr = formatDate2(today);
        var tomorrowStr = formatDate2(tomorrow);
        
        // ✅ 優化：延遲到瀏覽器空閒時才預加載
        function preloadDates() {
            $.ajax({
                url: kayarine_vars.ajax_url,
                data: { action: 'kayarine_proxy_check', date: todayStr },
                method: 'POST',
                dataType: 'json',
                timeout: 3000,
                error: function() { /* 忽略 */ }
            });
            
            $.ajax({
                url: kayarine_vars.ajax_url,
                data: { action: 'kayarine_proxy_check', date: tomorrowStr },
                method: 'POST',
                dataType: 'json',
                timeout: 3000,
                error: function() { /* 忽略 */ }
            });
        }
        
        // 現代瀏覽器：使用 requestIdleCallback（不阻塞主線程）
        if (typeof requestIdleCallback !== 'undefined') {
            requestIdleCallback(preloadDates);
        } else {
            // 降級方案：延遲 1.5 秒後預加載（在頁面加載完成後）
            setTimeout(preloadDates, 1500);
        }
    })();

    // ----------------------------------------------------
    // 2.5. Stock Check Logic (API)
    // ----------------------------------------------------
    async function checkStock(date, itemsRequested) {
        // Use the proper WP AJAX URL localized in 'kayarine_vars' not 'kayarine_config'
        // 'kayarine_config.api_url' was set to admin-ajax?action=kayarine_proxy_check
        // But let's be safe and use standard ajax pattern if possible
        var ajaxUrl = kayarine_vars.ajax_url;
        
        try {
            // Using POST to avoid potential length limits or caching issues with GET
            // Also standardizing action parameter location
            const response = await $.ajax({
                url: ajaxUrl,
                data: {
                    action: 'kayarine_proxy_check',
                    date: date
                },
                method: 'POST',
                dataType: 'json'
            });

            // The PHP side uses wp_send_json(), which might return structure:
            // { status: 'success', availability: ... }
            // OR if wp_send_json_success is used: { success: true, data: { ... } }
            // Let's robustly check both
            
            var isSuccess = false;
            var availabilityData = null;

            if (response.status === 'success') {
                isSuccess = true;
                availabilityData = response.availability;
            } else if (response.success === true) {
                isSuccess = true;
                // If using standard WP success, data is in response.data
                // But our custom function sends flat array sometimes.
                // Let's check where availability is.
                availabilityData = response.data ? response.data.availability : (response.availability || response.data);
            }

            if (isSuccess) {
                var availability = availabilityData;
                var errors = [];

                if (!availability) {
                     console.error("API returned success but no availability data found.", response);
                     return { success: false, message: "System Error: Invalid server response." };
                }

                console.log("Availability Data:", availability);

                // Check each requested item
                for (var i = 0; i < itemsRequested.length; i++) {
                    var item = itemsRequested[i];
                    var pid = item.id;
                    var qty = item.qty;

                    if (availability[pid]) {
                        var remaining = parseInt(availability[pid].remaining);
                        var name = availability[pid].name;
                        
                        console.log("Checking Item " + pid + " (" + name + "): Requested " + qty + ", Remaining " + remaining);

                        if (qty > remaining) {
                            errors.push(name + ": Only " + remaining + " left (You asked for " + qty + ")");
                        }
                    } else {
                        // Product ID might not be in the returned list if limit is not defined?
                        // Or if undefined constant error caused partial response.
                        console.warn("Item " + pid + " not found in availability response.");
                    }
                }

                if (errors.length > 0) {
                    return { success: false, message: "⚠️ Not enough stock:\n" + errors.join("\n") };
                }
                return { success: true };
            } else {
                console.error("API Error:", response);
                return { success: false, message: "System Error: Could not verify stock. Please contact support." };
            }

        } catch (error) {
            console.error("Stock Check Failed:", error);
            // Don't block user on connection error - allow them to try add to cart,
            // the server will validate again anyway.
            // OR return specific message.
            // Prompt said: "Connection Error: Cannot connect to inventory server."
            // It might be due to 404 on API URL or Network.
            // Let's assume server is up but maybe URL is wrong in JS.
            return { success: false, message: "Connection Error: Cannot verify stock availability. Please try again or contact us." };
        }
    }

    // ----------------------------------------------------
    // 3. Unified Booking Logic (One-Page Booking)
    // ----------------------------------------------------
    if ($('#kayarine-unified-booking').length > 0) {
        
        // Use a different date input ID for unified
        var $uniDate = $('#kub_date');
        
        var fpInstance;
    
        // Helper: Determine month count
        function getMonthCount() {
            // Mobile (iPhone SE etc) < 500px -> 1 month
            // Tablets (iPad Portrait) < 900px -> 1 month
            // Desktop / Landscape Tablet >= 900px -> 2 months
            return window.innerWidth < 900 ? 1 : 2;
        }
    
        function initFlatpickr() {
            if (typeof flatpickr === 'undefined') return;
    
            // Destroy existing instance if any
            if (fpInstance) {
                fpInstance.destroy();
            }
    
            var months = getMonthCount();
    
            fpInstance = flatpickr("#kub_date", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disable: disableDates,
                inline: true,
                showMonths: months,
                locale: { firstDayOfWeek: 0 },
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    // Optimized: Lightweight day create logic (same as single product)
                    var date = dayElem.dateObj;
                    var day = date.getDay();
                    
                    if (day === 0 || day === 6) {
                        dayElem.classList.add('kb-date-weekend');
                    }
                    
                    if (config.holidays && config.holidays.length < 50) {
                        var formatted = formatDate(date);
                        if (config.holidays.includes(formatted)) {
                            dayElem.classList.add('kb-date-holiday');
                        }
                    }
                },
                onChange: function(selectedDates, dateStr) {
                     if (dateStr) {
                        $('#kb-selected-date-display').text('Selected Date: ' + dateStr).show();
                     } else {
                        $('#kb-selected-date-display').hide();
                     }
                      calculateUnifiedTotal();
                 }
            });
        }
    
        // Initialize
        initFlatpickr();
    
        // Debounced Resize Listener
        var resizeTimer;
        var currentMonths = getMonthCount();
    
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                var newMonths = getMonthCount();
                // Only re-init if the REQUIRED number of months has changed
                // This prevents unnecessary reloads on small resizes
                if (newMonths !== currentMonths) {
                    currentMonths = newMonths;
                    initFlatpickr();
                }
            }, 200);
        });

        function calculateUnifiedTotal() {
            var dateVal = $uniDate.val();
            var $display = $('#kub-total-price');
            
            // Default to weekday prices if no date selected yet, or handle empty date
            var priceType = 'weekday';
            
            if (dateVal) {
                var dateObj = new Date(dateVal);
                var day = dateObj.getDay();
                if (day === 0 || day === 6 || config.holidays.includes(dateVal)) {
                    priceType = 'weekend';
                }
            }

            var total = 0;

            // Loop all inputs
            $('.kb-val').each(function() {
                var $input = $(this);
                var qty = parseInt($input.val()) || 0;
                var id = $input.data('id');

                // Skip if not a unified product ID
                if (!id) return;

                if (qty > 0 && config.prices[id]) {
                    var unitPrice = config.prices[id][priceType];
                    total += (unitPrice * qty);
                }

                // Update Visual State of the Box (Equipment Grid)
                // New Selector: .kb-card
                var $card = $input.closest('.kb-card');
                if ($card.length > 0) {
                    var $priceText = $card.find('.kb-card-price');
                    
                    // Update price text based on selection (or default weekday if no date)
                    if (config.prices[id]) {
                         $priceText.text('HKD' + config.prices[id][priceType]);
                    }

                    if (qty > 0) {
                        $card.addClass('kb-selected');
                        // Template doesn't show badge, just border highlight if anything
                    } else {
                        $card.removeClass('kb-selected');
                    }
                }
            });
            
            if (!dateVal) {
                 $display.text('$ -');
            } else {
                 $display.text('$' + total);
            }
        }

        // Trigger on qty change
        // Selector: .kb-val
        $('.kb-val').on('change', calculateUnifiedTotal);

        // ----------------------------------------------------
        // 3.5. Long Press Feature (Unified Items)
        // ----------------------------------------------------
        var pressTimer;
        
        // Use delegated event since items might change or init
        // Select .kb-card for grid items (equipment)
        // Updated selector to .kb-card-img
        $(document).on('click', '.kb-card-img', function(e) {
            e.preventDefault();
            var $card = $(this).closest('.kb-card');
            
            var infoRaw = $card.data('info');
            if (!infoRaw) return;

            // Open Modal
            var info = typeof infoRaw === 'object' ? infoRaw : JSON.parse(infoRaw);
            $('#kb-modal-title').text(info.name);
            $('#kb-modal-desc').text(info.desc || 'No description');
            $('#kb-modal-weight').text(info.weight || '-');
            $('#kb-modal-persons').text(info.persons || '-');
            
            $('#kb-info-modal').fadeIn(200);
        });

        // Modal Close
        $('.kb-modal-close, .kb-modal-overlay').on('click', function(e) {
            if (e.target !== this) return; // Prevent close if clicking content
            $('#kb-info-modal').fadeOut(200);
        });

        // Submit Handler (AJAX)
        $('#kub-submit-btn').on('click', function(e) {
            e.preventDefault();
            var date = $uniDate.val();
            if (!date) {
                alert('請選擇日期 Please select a date.');
                return;
            }

            var items = [];
            // Selector: .kb-val with data-id
            $('.kb-val[data-id]').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    items.push({
                        id: $(this).data('id'),
                        qty: qty
                    });
                }
            });

            if (items.length === 0) {
                alert('請選擇至少一樣裝備 Please select at least one item.');
                return;
            }

            // AJAX Request
            var $btn = $(this);
            var originalText = $btn.text();
            
            // 1. Check Stock First
            $btn.text('Checking Availability...').prop('disabled', true);
            $('#kub-error-msg').hide();

            checkStock(date, items).then(function(stockResult) {
                if (!stockResult.success) {
                    alert(stockResult.message);
                    $btn.text(originalText).prop('disabled', false);
                    return;
                }

                // 2. Proceed to Add to Cart
                $btn.text('Processing...');
                
                $.ajax({
                    url: kayarine_vars.ajax_url, // Make sure we localize this URL
                    type: 'POST',
                    data: {
                        action: 'kayarine_add_bundle_to_cart',
                        date: date,
                        items: items
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.redirect;
                        } else {
                            alert(response.data.message);
                            $btn.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error adding to cart.');
                        $btn.text(originalText).prop('disabled', false);
                    }
                });
            });
        });
    }

    // ----------------------------------------------------
    // 4. Form Validation (Intercept Single Product Submit)
    // ----------------------------------------------------
    $form.on('submit', function(e) {
        var date = $dateInput.val();
        if (!date) {
            alert('請選擇預訂日期 Please select a booking date.');
            e.preventDefault();
            return false;
        }

        // Single Product Stock Check
        // We need to pause submission, check stock, then submit if OK.
        // This is tricky with standard form submit.
        
        // Check if we already validated
        if ($form.data('stock-validated') === true) {
            return true;
        }

        e.preventDefault();
        
        // Use our main quantity pill value
        var qty = parseInt( $('#kb-main-qty-display').val() ) || 1;
        var items = [{ id: productId, qty: qty }];

        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.text();
        $btn.text('Checking...').prop('disabled', true);

        checkStock(date, items).then(function(stockResult) {
            if (!stockResult.success) {
                alert(stockResult.message);
                $btn.text(originalText).prop('disabled', false);
            } else {
                // Success - Mark validated and resubmit
                $form.data('stock-validated', true);
                $btn.prop('disabled', false).text(originalText); // Restore UI
                $form.submit();
            }
        });
    });

    // ============================================
    // 5. Cart Page Quantity Sync (購物車頁面數量同步)
    // ============================================
    // 監聽購物車頁面數量輸入框變更，觸發結帳更新
    if ($('.woocommerce-cart').length > 0) {
        // 當購物車數量改變時，延遲觸發結帳更新
        $(document).on('change', 'input.qty', function() {
            var $input = $(this);
            var newQty = parseInt($input.val()) || 0;
            
            console.log('[Kayarine] Cart quantity changed: ' + newQty);
            
            // 延遲執行以確保 WooCommerce 已更新內部狀態
            setTimeout(function() {
                // 觸發 WooCommerce 結帳更新事件
                $('body').trigger('update_checkout');
                console.log('[Kayarine] Triggered update_checkout event');
            }, 300);
        });
        
        // 監聽「更新購物車」按鈕點擊
        $(document).on('click', 'button[name="update_cart"]', function() {
            console.log('[Kayarine] Update Cart button clicked');
            setTimeout(function() {
                $('body').trigger('update_checkout');
                console.log('[Kayarine] Triggered update_checkout after update button');
            }, 500);
        });
    }

});
