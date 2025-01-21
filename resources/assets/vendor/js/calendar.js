document.addEventListener('DOMContentLoaded', function() {
    // تعريف الأشهر العربية
    const arabicMonths = [
        "يناير", "فبراير", "مارس", "إبريل", "مايو", "يونيو",
        "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"
    ];

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth() + 1;
    let currentYear = currentDate.getFullYear();

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // تحديث عنوان الشهر والسنة
    function updateMonthYear() {
        const months = [
            'يناير', 'فبراير', 'مارس', 'إبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];
        document.getElementById('currentMonthYear').textContent = `${months[currentMonth - 1]} ${currentYear}`;
    }

    // تحديث التقويم
    async function updateCalendar() {
      try {
          // إرسال طلب إلى السيرفر لجلب الأحداث
          const response = await fetch(`/app-calendar-events?month=${currentMonth}&year=${currentYear}`, {
              method: 'GET',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrfToken // التحقق من CSRF
              }
          });

          // التحقق من حالة الاستجابة
          if (!response.ok) {
              console.error(`Failed to fetch events: HTTP error! status: ${response.status}`);
              return; // الخروج إذا كان هناك خطأ في الاستجابة
          }

          // قراءة البيانات بصيغة JSON
          const data = await response.json();

          // التحقق من نجاح العملية
          if (data.status === 'success') {
            console.log('Events fetched successfully:', data.data);

            // التعامل مع البيانات المسترجعة
            const eventsByDate = data.data.reduce((acc, event) => {
                const date = event.start.split(' ')[0];
                acc[date] = acc[date] || [];
                acc[date].push(event);
                return acc;
            }, {});

            updateCalendarDays(eventsByDate);
        } else {
            console.error('Failed to fetch events:', data.message || 'Unknown error');
        }
      } catch (error) {
          console.error('Error fetching events:', error);
      }

  }

    // تحديث أيام التقويم
    function updateCalendarDays(events) {
        const firstDay = new Date(currentYear, currentMonth - 1, 1);
        const lastDay = new Date(currentYear, currentMonth, 0);
        const daysInMonth = lastDay.getDate();
        const startingDay = firstDay.getDay();

        const calendarDays = document.querySelector('.calendar .days');
        if (!calendarDays) return;

        // حذف كل الأيام الموجودة
        const dayElements = calendarDays.querySelectorAll('.day:not(.day-label)');
        dayElements.forEach(day => day.remove());

        // إضافة الأيام السابقة من الشهر السابق
        const prevMonth = new Date(currentYear, currentMonth - 1, 0);
        const daysInPrevMonth = prevMonth.getDate();
        for (let i = startingDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            const dayElement = createDayElement(day, true);
            calendarDays.appendChild(dayElement);
        }

        // إضافة أيام الشهر الحالي
        for (let day = 1; day <= daysInMonth; day++) {
            const date = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayEvents = events[date] || [];
            const dayElement = createDayElement(day, false, dayEvents);
            calendarDays.appendChild(dayElement);
        }

        // إضافة الأيام من الشهر التالي
        const remainingDays = 42 - (startingDay + daysInMonth);
        for (let day = 1; day <= remainingDays; day++) {
            const dayElement = createDayElement(day, true);
            calendarDays.appendChild(dayElement);
        }

        // إضافة معالج النقر للأيام التي تحتوي على أحداث
        const eventDays = document.querySelectorAll('.calendar .day.event');
        eventDays.forEach(day => {
            day.addEventListener('click', function() {
                const title = this.getAttribute('data-title');
                const description = this.getAttribute('data-description');
                const date = new Date(this.getAttribute('data-date'));

                const modalTitle = document.getElementById('eventModalLabel');
                const modalDescription = document.getElementById('eventDescription');
                const modalDate = document.getElementById('eventDate');

                if (modalTitle && modalDescription && modalDate) {
                    modalTitle.textContent = title;
                    modalDescription.textContent = description;

                    // تنسيق التاريخ بالعربية
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    modalDate.textContent = date.toLocaleDateString('ar-SA', options);
                }
            });
        });
    }

    // إنشاء عنصر يوم
    function createDayElement(day, isDull, events = []) {
        const dayElement = document.createElement('div');
        
        // التحقق مما إذا كان هذا اليوم هو اليوم الحالي
        const today = new Date();
        const currentDay = new Date(currentYear, currentMonth - 1, day);
        const isToday = !isDull && 
            today.getDate() === day && 
            today.getMonth() === currentMonth - 1 && 
            today.getFullYear() === currentYear;

        // إضافة الكلاسات
        dayElement.className = `day${isDull ? ' dull' : ''}${events.length > 0 ? ' event' : ''}${isToday ? ' today' : ''}`;

        if (events.length > 0) {
            dayElement.setAttribute('data-bs-toggle', 'modal');
            dayElement.setAttribute('data-bs-target', '#eventModal');
            dayElement.setAttribute('data-title', events[0].title);
            dayElement.setAttribute('data-description', events[0].extendedProps.description || '');
            dayElement.setAttribute('data-date', events[0].start);
        }

        const content = document.createElement('div');
        content.className = 'content';
        content.textContent = day;
        dayElement.appendChild(content);

        return dayElement;
    }

    // الشهر السابق
    function prevMonth() {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        updateMonthYear();
        updateCalendar();
    }

    // الشهر التالي
    function nextMonth() {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        updateMonthYear();
        updateCalendar();
    }

    // Add navigation buttons if they don't exist
    const monthYearElement = document.querySelector('.month-year');
    if (monthYearElement && !document.querySelector('.nav-btn')) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'nav-btn prev-month';
        prevBtn.innerHTML = '&#10094;';

        const nextBtn = document.createElement('button');
        nextBtn.className = 'nav-btn next-month';
        nextBtn.innerHTML = '&#10095;';

        monthYearElement.insertBefore(prevBtn, monthYearElement.firstChild);
        monthYearElement.appendChild(nextBtn);

        prevBtn.addEventListener('click', prevMonth);
        nextBtn.addEventListener('click', nextMonth);
    }

    // استمع لتغييرات قاعدة البيانات
    document.addEventListener('databaseChanged', function() {
        updateCalendar();
    });

    // تحديث التقويم عند تحميل الصفحة
    updateMonthYear();
    updateCalendar();
});
