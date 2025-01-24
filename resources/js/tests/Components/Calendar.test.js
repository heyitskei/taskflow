import {mount} from '@vue/test-utils';
import {afterEach, beforeEach, describe, expect, vi} from 'vitest';
import Calendar from '../../Components/Calendar.vue';
import axios from 'axios';

vi.mock('axios');

let wrapper;
let mockEvents;

describe('Calendar Component', () => {
    beforeEach(() => {
        // Set a fixed date for testing
        const fixedDate = new Date('2024-01-01T12:00:00.000Z');
        vi.setSystemTime(fixedDate);

        // Initialize mock events for testing
        mockEvents = [
            {
                id: 1,
                title: 'Test Event',
                start_datetime: '2024-01-01 09:00:00',
                end_datetime: '2024-01-01 10:00:00'
            }
        ];

        // Mount component with required props
        wrapper = mount(Calendar, {
            props: {
                events: mockEvents,
                selectedDate: fixedDate
            }
        });
    });

    afterEach(() => {
        wrapper.unmount();
        vi.clearAllMocks();
    });

    test('renders correctly', () => {
        expect(wrapper.exists()).toBe(true);
    });

    test('displays current month and year', async () => {
        await wrapper.vm.$nextTick();
        const monthElement = wrapper.find('h2.text-xl.font-semibold');
        const yearElement = wrapper.find('span.text-sm.text-gray-500');
        expect(monthElement.exists()).toBe(true);
        expect(yearElement.exists()).toBe(true);
        expect(monthElement.text()).toBe('January');
        expect(yearElement.text()).toBe('2024');
    });

    test('displays events in calendar', async () => {
        // Wait for the component to update
        await wrapper.vm.$nextTick();

        // Find all event elements
        const eventElements = wrapper.findAll('[draggable="true"]');
        expect(eventElements.length).toBe(1);
        expect(eventElements[0].text()).toBe('Test Event');
    });

    test('can navigate to previous month', async () => {
        const prevButton = wrapper.find('button:first-child');
        await prevButton.trigger('click');
        await wrapper.vm.$nextTick();

        const monthElement = wrapper.find('h2.text-xl.font-semibold');
        const yearElement = wrapper.find('span.text-sm.text-gray-500');
        expect(monthElement.text()).toBe('December');
        expect(yearElement.text()).toBe('2023');
    });

    test('can navigate to next month', async () => {
        const nextButton = wrapper.find('button:last-child');
        await nextButton.trigger('click');
        await wrapper.vm.$nextTick();

        const monthElement = wrapper.find('h2.text-xl.font-semibold');
        const yearElement = wrapper.find('span.text-sm.text-gray-500');
        expect(monthElement.text()).toBe('February');
        expect(yearElement.text()).toBe('2024');
    });

    test('can reset to today', async () => {
        // First navigate away from current month
        const nextButton = wrapper.find('button:last-child');
        await nextButton.trigger('click');
        await wrapper.vm.$nextTick();

        // Then click today button
        const todayButton = wrapper.find('button.text-sm.font-medium.text-blue-600');
        expect(todayButton.exists()).toBe(true);
        await todayButton.trigger('click');
        await wrapper.vm.$nextTick();

        const monthElement = wrapper.find('h2.text-xl.font-semibold');
        const yearElement = wrapper.find('span.text-sm.text-gray-500');
        expect(monthElement.text()).toBe('January');
        expect(yearElement.text()).toBe('2024');
    });

    test('handles drag and drop of events', async () => {
        await wrapper.vm.$nextTick();
        const event = wrapper.find('[draggable="true"]');
        expect(event.exists()).toBe(true);

        // Mock successful API response
        axios.put.mockResolvedValueOnce({
            data: {
                event: {
                    id: 1,
                    title: 'Test Event',
                    start_datetime: '2024-01-02 09:00:00',
                    end_datetime: '2024-01-02 10:00:00'
                }
            }
        });

        // Create drag event data
        const dragData = {
            id: 1,
            title: 'Test Event',
            start_datetime: '2024-01-01 09:00:00',
            end_datetime: '2024-01-01 10:00:00'
        };

        // Simulate drag and drop
        await event.trigger('dragstart', {
            dataTransfer: {
                setData: vi.fn(),
                effectAllowed: 'move',
                getData: () => JSON.stringify(dragData)
            }
        });

        const dropTarget = wrapper.find('.bg-white.p-1\\.5');
        await dropTarget.trigger('drop', {
            dataTransfer: {
                getData: () => JSON.stringify(dragData)
            }
        });

        expect(axios.put).toHaveBeenCalled();
    });

    test('handles error during event update', async () => {
        await wrapper.vm.$nextTick();
        const event = wrapper.find('[draggable="true"]');
        expect(event.exists()).toBe(true);

        // Mock failed API response
        axios.put.mockRejectedValueOnce(new Error('Failed to update'));

        // Create drag event data
        const dragData = {
            id: 1,
            title: 'Test Event',
            start_datetime: '2024-01-01 09:00:00',
            end_datetime: '2024-01-01 10:00:00'
        };

        // Mock window.alert
        const alertMock = vi.spyOn(window, 'alert').mockImplementation(() => {
        });

        // Simulate drag and drop
        await event.trigger('dragstart', {
            dataTransfer: {
                setData: vi.fn(),
                effectAllowed: 'move',
                getData: () => JSON.stringify(dragData)
            }
        });

        const dropTarget = wrapper.find('.bg-white.p-1\\.5');
        await dropTarget.trigger('drop', {
            dataTransfer: {
                getData: () => JSON.stringify(dragData)
            }
        });

        expect(alertMock).toHaveBeenCalledWith('Failed to update event. Please try again.');
        alertMock.mockRestore();
    });

    test('navigates to previous month when clicking on a previous month day', async () => {
        await wrapper.vm.$nextTick();

        // Find the first day cell (usually from previous month)
        const firstDayCell = wrapper.find('.opacity-50');
        expect(firstDayCell.exists()).toBe(true);

        await firstDayCell.trigger('click');
        await wrapper.vm.$nextTick();

        const monthElement = wrapper.find('h2.text-xl.font-semibold');
        const yearElement = wrapper.find('span.text-sm.text-gray-500');
        expect(monthElement.text()).toBe('December');
        expect(yearElement.text()).toBe('2023');
    });

    test('navigates to next month when clicking on a next month day', async () => {
        await wrapper.vm.$nextTick();

        // Find all day cells and get the last one (usually from next month)
        const dayCells = wrapper.findAll('.bg-white.p-1\\.5');
        const lastDayCell = dayCells[dayCells.length - 1];
        expect(lastDayCell.exists()).toBe(true);

        await lastDayCell.trigger('click');
        await wrapper.vm.$nextTick();

        const monthElement = wrapper.find('h2.text-xl.font-semibold');
        const yearElement = wrapper.find('span.text-sm.text-gray-500');
        expect(monthElement.text()).toBe('February');
        expect(yearElement.text()).toBe('2024');
    });
});
