<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('membership_number')
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn (Member $record) => $record->fullName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('membershipPlan.name')
                    ->label('Plan'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled']),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->action(function () {
                        return new StreamedResponse(function () {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Membership Number', 'Name', 'Email', 'Plan', 'Status', 'Joined', 'Expires']);

                            Member::query()->with('membershipPlan')->orderBy('last_name')->each(function (Member $member) use ($handle) {
                                fputcsv($handle, [
                                    $member->membership_number,
                                    $member->fullName(),
                                    $member->email,
                                    $member->membershipPlan?->name,
                                    $member->status,
                                    $member->joined_at?->toDateString(),
                                    $member->expires_at?->toDateString(),
                                ]);
                            });

                            fclose($handle);
                        }, 200, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="members.csv"',
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
