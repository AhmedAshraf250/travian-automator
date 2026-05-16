
## Runtime Note

This reference is now mirrored in code through:

- `app/Application/Travian/TravianBuildingCatalog.php`

Current runtime uses for that catalog include:

- resolving `gid -> name`
- resolving `name -> gid`
- detecting `field` vs `building` queue kind
- detecting tribe-specific availability
- resolving fixed slots such as:
  - `slot 26 -> gid 15` main building
  - `slot 39 -> gid 16` rally point
  - wall slots by tribe

The markdown file remains the human-facing source map, while the PHP catalog is the executable reference used by sync, automation, and village settings.

---{Resources}---
Woodcutter                  | gid=1     | "الحطاب "
Clay Pit                    | gid=2     | "حفرة الطين"
Iron Mine                   | gid=3     | "منجم حديد"
Cropland                    | gid=4     | "حقل القمح"
Sawmill                     | gid=5     | "معمل النجارة"
Brickyard                   | gid=6     | "مصنع البلوك"
Iron Foundry                | gid=7     | "مصنع الحديد"
Grain Mill                  | gid=8     | "المطاحن"
Bakery                      | gid=9     | "المخابز"

---{Infrastructure}---
Warehouse                   | gid=10    | "المخزن"
Granary                     | gid=11    | "مخزن الحبوب"
Main Building               | gid=15    | "المبنى الرئيسي"
Rally Point                 | gid=16    | "نقطة التجمع"
Marketplace                 | gid=17    | "السوق"
Embassy                     | gid=18    | "السفارة"
Cranny                      | gid=23    | "المخبأ"
Town Hall                   | gid=24    | "البلدية"
Residence                   | gid=25    | "السكن"
Palace                      | gid=26    | "القصر"
Treasury                    | gid=27    | "الخزنة"
Trade Office                | gid=28    | "المكتب التجاري"
City Wall(Roman)            | gid=31    | "حائط المدينة"
Earth Wall(Teutons)         | gid=32    | "الحائط الأرضي"
Palisade(Gauls)             | gid=33    | "الحاجز"
Stonemason's Lodge          | gid=34    | "الحجار"
Brewery(Teutons)            | gid=35    | "المقهى"
Trapper(Gauls)              | gid=36    | "الصياد"
Great Warehouse             | gid=38    | "المخزن الكبير"
Great Granary               | gid=39    | "مخزن الحبوب الكبير"
Horse Drinking Trough(Roman)| gid=41    | "بِئْر سقي الخيول"

---{Military}---
Smithy                      | gid=13    | "أفران صهر الحديد"
Tournament Square           | gid=14    | "ساحة البطولة"
Barracks                    | gid=19    | "الثكنة"
Stable                      | gid=20    | "الإسطبل"
Workshop                    | gid=21    | "المصانع الحربية"
Academy                     | gid=22    | "الأكاديمية"
Hero's Mansion              | gid=37    | "قصر الأبطال"
Hospital                    | gid=46    | "مستشفى"
                            | 
