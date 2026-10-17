<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Models\ContentPage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten & Halaman';

    protected static ?string $navigationLabel = 'Halaman Informasi';

    protected static ?string $modelLabel = 'Halaman Konten';

    protected static ?string $pluralModelLabel = 'Daftar Halaman Konten';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Utama Halaman')
                    ->description('Tuliskan judul, slug URL, dan artikel lengkap untuk halaman informasi.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Halaman')
                            ->placeholder('Contoh: Tentang RGB Arena')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set, $get) {
                                if ($operation === 'create' && empty($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Slug URL (/informasi/{slug})')
                            ->placeholder('contoh: about, rules, panduan-bermain')
                            ->required()
                            ->unique(ContentPage::class, 'slug', ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('Digunakan pada tautan alamat web browser. Format: huruf kecil, angka, dan strip.'),

                        Textarea::make('excerpt')
                            ->label('Ringkasan / Excerpt')
                            ->placeholder('Tuliskan ringkasan singkat halaman ini untuk ditampilkan pada preview kartu...')
                            ->rows(2)
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label('Isi Lengkap Halaman')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'strike',
                                'link',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Banner & Pengaturan Publikasi')
                    ->description('Atur gambar tajuk, kategori, serta visibilitas halaman di situs publik.')
                    ->schema([
                        FileUpload::make('featured_image')
                            ->label('Gambar Banner Tajuk')
                            ->image()
                            ->disk('public')
                            ->directory('pages')
                            ->helperText('Gambar horizontal yang akan tampil sebagai banner atas halaman.'),

                        Select::make('category')
                            ->label('Kategori Halaman')
                            ->options([
                                'general' => 'Informasi Umum',
                                'rules' => 'Aturan & Protokol',
                                'facilities' => 'Fasilitas & Perlengkapan',
                                'policies' => 'Kebijakan & Privasi',
                                'faq' => 'Tanya Jawab (FAQ)',
                            ])
                            ->default('general')
                            ->required(),

                        Toggle::make('is_published')
                            ->label('Publikasikan Halaman')
                            ->helperText('Jika aktif, halaman langsung dapat diakses oleh publik.')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampilan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nilai angka lebih kecil akan tampil lebih awal.'),
                    ]),

                Section::make('Optimasi Mesin Pencari (SEO)')
                    ->description('Pengaturan meta tag untuk pencarian Google dan sharing media sosial.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->placeholder('Judul spesifik untuk hasil pencarian Google'),

                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->placeholder('Deskripsi ringkas yang tampil di snippet Google...')
                            ->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Banner')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => !empty($record->featured_image) && !str_starts_with($record->featured_image, 'http') ? asset($record->featured_image) : null),

                TextColumn::make('title')
                    ->label('Judul Halaman')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray'),

                BadgeColumn::make('category')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'rules' => 'Aturan & Panduan',
                        'facilities' => 'Fasilitas Tempur',
                        'policies' => 'Kebijakan',
                        'faq' => 'FAQ',
                        default => 'Informasi Umum',
                    })
                    ->colors([
                        'warning' => 'rules',
                        'info' => 'facilities',
                        'danger' => 'policies',
                        'primary' => 'general',
                    ]),

                IconColumn::make('is_published')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'general' => 'Informasi Umum',
                        'rules' => 'Aturan & Panduan',
                        'facilities' => 'Fasilitas Tempur',
                        'policies' => 'Kebijakan',
                        'faq' => 'Tanya Jawab',
                    ]),

                TernaryFilter::make('is_published')
                    ->label('Status Publikasi')
                    ->trueLabel('Hanya Tayang')
                    ->falseLabel('Hanya Draf'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('view_page')
                    ->label('Lihat')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ContentPage $record) => route('pages.show', $record->slug))
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
            'create' => CreateContentPage::route('/create'),
            'edit' => EditContentPage::route('/{record}/edit'),
        ];
    }
}
