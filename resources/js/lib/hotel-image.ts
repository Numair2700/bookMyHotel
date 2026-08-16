// Deterministic placeholder photo per hotel — no database field, just a stable
// mapping from the hotel id onto the images in public/images/hotels/.
const IMAGE_COUNT = 5;

export function hotelImage(id: number): string {
    const index = (((id - 1) % IMAGE_COUNT) + IMAGE_COUNT) % IMAGE_COUNT;

    return `/images/hotels/${index + 1}.jpg`;
}
