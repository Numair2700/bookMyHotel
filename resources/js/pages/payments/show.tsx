import { Head } from '@inertiajs/react';

interface PaymentDetail {
    id: number;
    reservation_reference: string | null;
    amount: number;
    method: string;
    status: string;
    gateway_reference: string | null;
    paid_at: string | null;
}

interface Props {
    payment: PaymentDetail;
}

// Phase 4 stub. Phase 5 replaces this with the styled receipt page.
export default function PaymentShow({ payment }: Props) {
    return (
        <>
            <Head title={`Payment ${payment.id}`} />
            <div className="mx-auto max-w-3xl p-6">
                <h1 className="text-xl font-semibold">Payment receipt</h1>
                <dl className="mt-4 space-y-1 text-sm">
                    <div>Reservation: {payment.reservation_reference}</div>
                    <div>Amount: {payment.amount}</div>
                    <div>Method: {payment.method}</div>
                    <div>Status: {payment.status}</div>
                    <div>Reference: {payment.gateway_reference}</div>
                    <div>Paid at: {payment.paid_at}</div>
                </dl>
            </div>
        </>
    );
}
