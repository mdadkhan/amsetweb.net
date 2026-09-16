<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('payable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                TextColumn::make('gateway'),
                TextColumn::make('amount')
                    ->money('usd'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('gateway_reference'),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('gateway')
                    ->options(['stripe' => 'Stripe', 'paypal' => 'PayPal']),
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'succeeded' => 'Succeeded', 'failed' => 'Failed', 'refunded' => 'Refunded']),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->action(function () {
                        return new StreamedResponse(function () {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['ID', 'Type', 'Gateway', 'Amount', 'Currency', 'Status', 'Reference', 'Paid At']);

                            Payment::query()->orderByDesc('id')->each(function (Payment $payment) use ($handle) {
                                fputcsv($handle, [
                                    $payment->id,
                                    class_basename($payment->payable_type),
                                    $payment->gateway,
                                    $payment->amount,
                                    $payment->currency,
                                    $payment->status,
                                    $payment->gateway_reference,
                                    $payment->paid_at?->toDateTimeString(),
                                ]);
                            });

                            fclose($handle);
                        }, 200, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="payments.csv"',
                        ]);
                    }),
            ]);
    }
}
