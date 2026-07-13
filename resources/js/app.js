import {createApp} from 'vue';
import {createPinia} from 'pinia';
import {createRouter, createWebHistory} from 'vue-router';
import App from './App.vue';
import {useAuthStore} from "@/stores/auth.js";
import HomePage from "./pages/HomePage.vue";


const routes = [
    {path: '/', name: 'home', component: HomePage, meta: {public: true}},
    {
        path: '/login',
        name: 'login',
        component: () => import('./pages/LoginPage.vue'),
        meta: {public: true}
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('./pages/RegisterPage.vue'),
        meta: {public: true}
    },
    {
        path: '/verify-email/:email?',
        name: 'verification.notice',
        component: () => import('./pages/VerifyEmailPage.vue'),
        meta: { public: true },
    },
    {
        path: '/verify-email-handler/email/verify/:id/:hash',
        name: 'verification.handler',
        component: () => import('./pages/EmailVerificationHandlerPage.vue'),
        meta: { public: true }
    },
    {
        path: '/not-found',
        name: 'not-found',
        component: () => import('./pages/NotFoundPage.vue')
    },
    {
        path: '/rezervacia',
        name: 'reservations.areas',
        component: () => import('./pages/AreasPage.vue'),
        meta: {public: true}
    },
    {
        path: '/rezervacia/potvrdenie/:id/:token',
        name: 'reservations.verify',
        component: () => import('./pages/ReservationVerificationHandlerPage.vue'),
        meta: {public: true}
    },
    {
        path: '/rezervacia/platba/navrat',
        name: 'reservations.payment-return',
        component: () => import('./pages/PaymentReturnPage.vue'),
        meta: {public: true}
    },
    {
        path: '/rezervacia/platba/pokracovat/:orderId',
        name: 'reservations.payment-resume',
        component: () => import('./pages/PaymentResumePage.vue'),
        meta: {public: true}
    },
    {
        path: '/rezervacia/ihrisko/:playgroundId',
        name: 'reservations.playground-detail',
        component: () => import('./pages/PlaygroundDetailPage.vue'),
        meta: {public: true}
    },
    {
        path: '/rezervacia/:playgroundId',
        name: 'reservations.booking',
        component: () => import('./pages/BookingPage.vue'),
        meta: {public: true}
    },
    {
        path: '/mapa',
        name: 'map',
        component: () => import('./pages/MapPage.vue'),
        meta: {public: true}
    },
    {
        path: '/kontakt',
        name: 'contact',
        component: () => import('./pages/ContactPage.vue'),
        meta: {public: true}
    },
    {
        path: '/ochrana-osobnych-udajov',
        name: 'gdpr',
        component: () => import('./pages/GdprPage.vue'),
        meta: {public: true}
    },
    {
        path: '/podmienky-pouzivania',
        name: 'terms',
        component: () => import('./pages/TermsPage.vue'),
        meta: {public: true}
    },
    {
        path: '/login/2fa',
        name: 'login.2fa',
        component: () => import('./pages/TwoFactorChallengePage.vue'),
        meta: { public: true } // Accessible before full authentication
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('./pages/ForgotPasswordPage.vue'),
        meta: { public: true }
    },
    {
        path: '/reset-password/:token',
        name: 'reset-password',
        component: () => import('./pages/ResetPasswordPage.vue'),
        meta: { public: true }
    },
    {
        path: '/admin',
        component: () => import('./components/layout/AdminLayout.vue'),
        meta: {requiresAuth: true, requiresVerify: true, permissions: ['view_admin']},
        children: [
            {
                path: '',
                name: 'admin-dashboard',
                component: () => import('./pages/admin/DashboardPage.vue'),
                meta: {title: 'Prehľad'}
            },
            {
                path: 'users',
                name: 'admin-users',
                component: () => import('./pages/admin/UsersPage.vue'),
                meta: {
                    title: 'Správa používateľov',
                    permissions: ['manage_users']
                }
            },
            {
                path: 'profile',
                name: 'AdminMyProfilePage',
                component: () => import('./pages/admin/AdminMyProfile.vue'),
                meta: {
                    title: 'Môj profil'
                }
            },
            {
                path: 'settings',
                name: 'AdminSettingsPage',
                component: () => import('./pages/admin/AdminSettingsPage.vue'),
                meta: {
                    title: 'Nastavenia',
                    permissions: ['manage_settings']
                }
            },
            {
                path: 'settings/all',
                name: 'AdminAllSettingsPage',
                component: () => import('./pages/admin/AdminAllSettingsPage.vue'),
                meta: {
                    title: 'Všetky nastavenia',
                    permissions: ['manage_settings']
                }
            },
            {
                path: 'facilities',
                name: 'admin-facilities',
                component: () => import('./pages/admin/AdminFacilitiesPage.vue'),
                meta: {
                    title: 'Areály a ihriská',
                    permissions: ['manage_facilities']
                }
            },
            {
                path: 'reservations',
                name: 'admin-reservations',
                component: () => import('./pages/admin/AdminReservationsPage.vue'),
                meta: {
                    title: 'Rezervácie',
                    permissions: ['manage_reservations']
                }
            }
        ]
    },
    {
        path: '/user',
        component: () => import('./components/layout/UserLayout.vue'),
        meta: {requiresAuth: true, requiresVerify: true},
        children: [
            {
                path: '',
                redirect: {name: 'user-dashboard'}
            },
            {
                path: 'dashboard',
                name: 'user-dashboard',
                component: () => import('./pages/user/UserDashboardPage.vue'),
                meta: {title: 'Prehľad'}
            },
            {
                path: 'profile',
                name: 'user-profile',
                component: () => import('./pages/admin/AdminMyProfile.vue'),
                meta: {title: 'Môj profil'}
            },
            {
                path: 'details',
                name: 'user-details',
                component: () => import('./pages/user/UserDetailsPage.vue'),
                meta: {title: 'Moje údaje'}
            },
            {
                path: 'reservations',
                name: 'user-reservations',
                component: () => import('./pages/user/UserReservationsPage.vue'),
                meta: {title: 'Moje rezervácie'}
            }
        ]
    },
    {
        path: '/:pathMatch(.*)*',  // Matches any route
        redirect: '/not-found'     // Redirect to not-found page
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to) {
        if (to.hash) {
            return {
                el: to.hash
            }
        }
    }
});

router.beforeEach(async (to, from, next) => {
    if (window.$vueApp && window.$vueApp.config.globalProperties.$loader) {
        window.$vueApp.config.globalProperties.$loader.show();
    }

    // Every show() above must be matched by afterEach's hide() below, or the
    // curtain is stuck until a hard refresh - vue-router only calls afterEach
    // for navigations that resolve normally (including cancelled/duplicate
    // ones via next(false)/next(error)), not for a guard that throws. Keeping
    // this whole body inside try/catch guarantees next() always runs.
    try {
        // Optional: Scroll to top on navigation
        window.scrollTo(0, 0);

        const authStore = useAuthStore();

        // Fetch user if token exists
        if (localStorage.getItem('auth_token') && !authStore.user) {
            await authStore.fetchUser();
        }

        // Check if route requires authentication
        if (to.meta.requiresAuth && !authStore.isAuthenticated) {
            return next({name: 'login'});
        }

        if (to.meta.requiresVerify && authStore.isAuthenticated && !authStore.user.email_verified_at) {
            return next({name: 'verification.notice'});
        }

        // Check permissions
        if (to.meta.permissions) {
            const hasPermission = to.meta.permissions.every(perm =>
                authStore.hasPermission(perm)
            );

            if (!hasPermission) {
                // An admin/editor missing just this one sub-permission stays
                // on their own admin dashboard instead of being bounced out of
                // the whole section; anyone without any admin access at all
                // lands on their user dashboard.
                if (to.path.startsWith('/admin')) {
                    return next(authStore.hasPermission('view_admin') ? {name: 'admin-dashboard'} : {name: 'user-dashboard'});
                }
                return next({name: 'not-found'});
            }
        }

        next();
    } catch (error) {
        console.error('Router guard error', error);
        next(false);
    }
});

router.afterEach(() => {
    if (window.$vueApp && window.$vueApp.config.globalProperties.$loader) {
        window.$vueApp.config.globalProperties.$loader.hide();
    }
});

const app = createApp(App);
window.$vueApp = app;
app.use(createPinia());
app.use(router);
app.mount('#app');
