import {mount} from '@vue/test-utils';
import {afterEach, beforeEach, describe, expect, vi} from 'vitest';
import DailyDetailedView from '../../Components/DailyDetailedView.vue';
import axios from 'axios';

vi.mock('axios');
vi.spyOn(window, 'alert').mockImplementation(() => {
});

let wrapper;
let mockEvents;

describe('DailyDetailedView Component', () => {
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

        // Mock axios
        axios.get.mockResolvedValue({data: mockEvents});
        axios.post.mockResolvedValue({
            data: {
                id: 2,
                title: 'New Test Event',
                start_datetime: '2024-01-01 14:00:00',
                end_datetime: '2024-01-01 15:00:00'
            }
        });
        axios.delete.mockResolvedValue({data: {success: true}});

        // Mount component with required props
        wrapper = mount(DailyDetailedView, {
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
        expect(wrapper.find('h2').text()).toBe('Daily Detailed View');
    });

    test('displays selected date', async () => {
        await wrapper.vm.$nextTick();
        const dateElement = wrapper.find('.text-lg.font-medium');
        expect(dateElement.exists()).toBe(true);
        expect(dateElement.text()).toBe('Monday, January 1, 2024');
    });

    test('displays events for selected date', async () => {
        await wrapper.vm.$nextTick();
        const eventElements = wrapper.findAll('.bg-white.rounded-lg.shadow-sm.border.border-gray-100.p-4');
        expect(eventElements.length).toBe(1);
        const eventText = eventElements[0].text();
        expect(eventText).toContain('Test Event');
    });

    test('can add new event', async () => {
        // Set the selected date
        await wrapper.setProps({
            selectedDate: new Date(Date.UTC(2024, 0, 1))
        });
        await wrapper.vm.$nextTick();

        // Click the add event button and wait for the form to appear
        const addButton = wrapper.find('button.w-full.py-2.px-4');
        await addButton.trigger('click');
        await wrapper.vm.$nextTick();

        // Find and fill in the input fields
        const titleInput = wrapper.find('input[placeholder="Event title"]');
        const startTimeInput = wrapper.findAll('input[type="time"]').at(0);
        const endTimeInput = wrapper.findAll('input[type="time"]').at(1);

        expect(titleInput.exists()).toBe(true);
        expect(startTimeInput.exists()).toBe(true);
        expect(endTimeInput.exists()).toBe(true);

        await titleInput.setValue('New Test Event');
        await startTimeInput.setValue('14:00');
        await endTimeInput.setValue('15:00');
        await wrapper.vm.$nextTick();

        // Mock successful API response
        axios.post.mockResolvedValueOnce({
            data: {
                event: {
                    id: 2,
                    title: 'New Test Event',
                    start_datetime: '2024-01-01 14:00:00',
                    end_datetime: '2024-01-01 15:00:00'
                }
            }
        });

        // Click the save button
        const saveButton = wrapper.find('button.bg-gradient-to-r');
        await saveButton.trigger('click');
        await wrapper.vm.$nextTick();

        // Verify the API call
        expect(axios.post).toHaveBeenCalledWith('/events', {
            title: 'New Test Event',
            start_datetime: '2024-01-01 14:00:00',
            end_datetime: '2024-01-01 15:00:00'
        });
    });

    test('validates event times', async () => {
        // Mock window.alert
        const alertMock = vi.spyOn(window, 'alert').mockImplementation(() => {
        });

        // Click add event button
        const addButton = wrapper.find('button.w-full.py-2.px-4.bg-blue-50');
        await addButton.trigger('click');
        await wrapper.vm.$nextTick();

        // Fill in event details with invalid times
        const titleInput = wrapper.find('input[placeholder="Event title"]');
        const startTimeInput = wrapper.find('input[type="time"]');
        const endTimeInput = wrapper.findAll('input[type="time"]')[1];

        await titleInput.setValue('Invalid Event');
        await startTimeInput.setValue('15:00');
        await endTimeInput.setValue('14:00');

        // Try to save event
        const saveButton = wrapper.find('button.bg-gradient-to-r');
        await saveButton.trigger('click');
        await wrapper.vm.$nextTick();

        expect(alertMock).toHaveBeenCalledWith('End time must be after start time');
        alertMock.mockRestore();
    });

    test('can delete event', async () => {
        await wrapper.vm.$nextTick();
        const eventElement = wrapper.find('.bg-white.rounded-lg.shadow-sm.border.border-gray-100.p-4');
        expect(eventElement.exists()).toBe(true);

        // Find and click delete button
        const deleteButton = eventElement.find('button.text-gray-400.hover\\:text-red-500');
        expect(deleteButton.exists()).toBe(true);
        await deleteButton.trigger('click');
        await wrapper.vm.$nextTick();

        // Verify the API call
        expect(axios.delete).toHaveBeenCalledWith('/events/1');
    });

    test('handles error when deleting event', async () => {
        // Mock the delete request to fail
        axios.delete.mockRejectedValueOnce(new Error('Failed to delete'));

        await wrapper.vm.$nextTick();
        const eventElement = wrapper.find('.bg-white.rounded-lg.shadow-sm.border.border-gray-100.p-4');
        expect(eventElement.exists()).toBe(true);

        // Find and click delete button
        const deleteButton = eventElement.find('button.text-gray-400.hover\\:text-red-500');
        expect(deleteButton.exists()).toBe(true);
        await deleteButton.trigger('click');

        // Wait for the error to be handled
        await new Promise(resolve => setTimeout(resolve, 100));

        // Verify the alert was shown with the correct message
        expect(window.alert).toHaveBeenCalledWith('Failed to delete event');
    });

    test('can cancel event creation', async () => {
        // Click add event button
        const addButton = wrapper.find('button.w-full.py-2.px-4.bg-blue-50');
        await addButton.trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('input[placeholder="Event title"]').exists()).toBe(true);

        // Click cancel button
        const cancelButton = wrapper.find('button.px-4.py-2.text-gray-600');
        expect(cancelButton.exists()).toBe(true);
        await cancelButton.trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('input[placeholder="Event title"]').exists()).toBe(false);
    });

    test('shows empty state when no date selected', async () => {
        await wrapper.setProps({selectedDate: null});
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.text-gray-600.text-center').text()).toBe('Select a date to view and manage events');
    });
});
