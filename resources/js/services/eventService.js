import axios from 'axios';

// Add CSRF token to all requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

export async function fetchEvents() {
    try {
        const response = await axios.get('/events');
        return response.data.map(event => ({
            ...event,
            color: '#3B82F6'
        }));
    } catch (error) {
        console.error('Error fetching events:', error);
        throw new Error('Failed to load events');
    }
}

export async function createEvent(eventData) {
    try {
        const response = await axios.post('/events', eventData);
        return {
            ...response.data,
            color: '#3B82F6'
        };
    } catch (error) {
        console.error('Error creating event:', error);
        console.error('Validation errors:', error.response?.data?.errors);
        throw new Error('Failed to create event');
    }
}

export async function updateEvent(eventId, eventData) {
    try {
        const response = await axios.put(`/events/${eventId}`, eventData);
        return response.data.event;
    } catch (error) {
        console.error('Error updating event:', error);
        throw new Error('Failed to update event');
    }
}

export async function deleteEvent(eventId) {
    try {
        await axios.delete(`/events/${eventId}`);
    } catch (error) {
        console.error('Error deleting event:', error);
        throw new Error('Failed to delete event');
    }
} 