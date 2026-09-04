import './bootstrap';
import Swiper from 'swiper';
import { A11y, Autoplay, Keyboard, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

function initializeSwipers() {
    document.querySelectorAll('.js-swiper:not(.swiper-initialized)').forEach((element) => {
        const autoplay = element.dataset.autoplay === 'true' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        new Swiper(element, {
            modules: [A11y, Autoplay, Keyboard, Navigation, Pagination],
            slidesPerView: 1.08,
            spaceBetween: 16,
            keyboard: { enabled: true },
            navigation: { nextEl: element.querySelector('.swiper-button-next'), prevEl: element.querySelector('.swiper-button-prev') },
            pagination: { el: element.querySelector('.swiper-pagination'), clickable: true },
            autoplay: autoplay ? { delay: 4500, disableOnInteraction: true, pauseOnMouseEnter: true } : false,
            breakpoints: { 768: { slidesPerView: 2.05, spaceBetween: 24 }, 1200: { slidesPerView: 2.6, spaceBetween: 28 } },
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeSwipers);
document.addEventListener('livewire:navigated', initializeSwipers);
