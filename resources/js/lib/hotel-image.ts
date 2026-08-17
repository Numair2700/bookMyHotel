// Deterministic placeholder photos — no database field, just a stable mapping
// from an id onto the images in public/images/hotels/. Covers are exterior /
// pool shots used to represent a hotel (hero + cards); rooms are interior shots
// used on the room-type cards.
const COVER_COUNT = 8;
const ROOM_COUNT = 4;

function pick(id: number, count: number): number {
    return (((id - 1) % count) + count) % count;
}

/** Exterior/pool shot standing in for the hotel itself. */
export function hotelCover(id: number): string {
    return `/images/hotels/covers/${pick(id, COVER_COUNT) + 1}.jpg`;
}

/** Interior shot for a room type (cycles across the room-photo set). */
export function roomImage(id: number): string {
    return `/images/hotels/rooms/${pick(id, ROOM_COUNT) + 1}.jpg`;
}
