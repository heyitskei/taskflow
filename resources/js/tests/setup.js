import '@testing-library/jest-dom';
import {vi} from 'vitest';

// Mock document.querySelector for CSRF token
document.querySelector = vi.fn((selector) => {
    if (selector === 'meta[name="csrf-token"]') {
        return {getAttribute: () => 'test-token'};
    }
    return null;
});

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Link: {
        name: 'Link',
        template: '<a><slot /></a>'
    },
    usePage: () => ({
        props: {
            value: {
                auth: {
                    user: {
                        id: 1,
                        name: 'Test User',
                        email: 'test@example.com'
                    }
                }
            }
        }
    })
}));

// Mock CSRF token
document.head.innerHTML = `<meta name="csrf-token" content="test-token">`;

// Mock axios
const mockAxios = {
    get: vi.fn(() => Promise.resolve({data: []})),
    post: vi.fn(() => Promise.resolve({data: {}})),
    put: vi.fn(() => Promise.resolve({data: {}})),
    delete: vi.fn(() => Promise.resolve({data: {}})),
    defaults: {
        headers: {
            common: {}
        }
    }
};

vi.mock('axios', () => ({
    default: mockAxios,
    get: mockAxios.get,
    post: mockAxios.post,
    put: mockAxios.put,
    delete: mockAxios.delete
}));

// Export mockAxios for use in tests
export {mockAxios};
