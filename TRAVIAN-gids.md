
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
Woodcutter                  | gid=1     | "الحطاب "             |
Clay Pit                    | gid=2     | "حفرة الطين"          |
Iron Mine                   | gid=3     | "منجم حديد"           | 
Cropland                    | gid=4     | "حقل القمح"           |
Sawmill                     | gid=5     | "معمل النجارة"        | Costs and total resources for level 1 : 520	380	290	90	1280	4         | Prerequisites: Woodcutter Level 10, Main Building Level 5                   | Max-level:5
Brickyard                   | gid=6     | "مصنع البلوك"         | Costs and total resources for level 1 : 440	480	320	50	1290	3         | Prerequisites: Clay Pit Level 10, Main Building Level 5                     | Max-level:5
Iron Foundry                | gid=7     | "مصنع الحديد"         | Costs and total resources for level 1 : 200	450	510	120	1280	6         | Prerequisites: Iron Mine Level 10, Main Building Level 5                    | Max-level:5        
Grain Mill                  | gid=8     | "المطاحن"             | Costs and total resources for level 1 : 500	440	380	1240	2560	3       | Prerequisites: Cropland Level 5                                             | Max-level:5
Bakery                      | gid=9     | "المخابز"             | Costs and total resources for level 1 : 1200	1480	870	1600	5150	4   | Prerequisites: Cropland Level 10, Main Building Level 5, Grain Mill Level 5 | Max-level:5

---{Infrastructure}---
Warehouse                   | gid=10    | "المخزن"              | Costs and total resources for level 1 : 130	160	90	40	420	1           | Prerequisites: Main Building Level 1
Granary                     | gid=11    | "مخزن الحبوب"         | Costs and total resources for level 1 : 80	100	70	20	270	1           | Prerequisites: Main Building Level 1
Main Building               | gid=15    | "المبنى الرئيسي"      | Costs and total resources for level 1 : 70	40	60	20	190	2           | Prerequisites: none
Marketplace                 | gid=17    | "السوق"               | Costs and total resources for level 1 : 80	70	120	70	340	4           | Prerequisites: Main Building Level 3, Warehouse Level 1, Granary Level 1
Embassy                     | gid=18    | "السفارة"             | Costs and total resources for level 1 : 180	130	150	80	540	3           | Prerequisites: Main Building Level 1
Cranny                      | gid=23    | "المخبأ"              | Costs and total resources for level 1 : 40	50	30	10	130	0           | Prerequisites: none
Town Hall                   | gid=24    | "البلدية"             | Costs and total resources for level 1 : 1250	1110	1260	600	4220	4   | Prerequisites: Main Building Level 10, Academy Level 10
Residence                   | gid=25    | "السكن"               | Costs and total resources for level 1 : 580	460	350	180	1570	1         | Prerequisites: Main Building Level 5
Palace                      | gid=26    | "القصر"               | Costs and total resources for level 1 : 550	800	750	250	2350	1         | Prerequisites: Embassy Level 1, Main Building Level 5
Treasury                    | gid=27    | "الخزنة"              | Costs and total resources for level 1 : 2880	2740	2580	990	9190	4   | Prerequisites: Main Building Level 10
Trade Office                | gid=28    | "المكتب التجاري"      | Costs and total resources for level 1 : 1400	1330	1200	400	4330	3   | Prerequisites: Marketplace Level 20, Stable Level 10
Stonemason's Lodge          | gid=34    | "الحجار"              | Costs and total resources for level 1 : 155	130	125	70	480	2           | Prerequisites: Main Building Level 5, capital
Brewery(Teutons)            | gid=35    | "المقهى"              | Costs and total resources for level 1 : 3210	2050	2750	3830	11840	6 | Prerequisites: Granary Level 20, Rally Point Level 10, capital
Great Warehouse             | gid=38    | "المخزن الكبير"       | Costs and total resources for level 1 : 650	800	450	200	2100	1         | Prerequisites: Main Building Level 10, Wonder Of The World Level 0
Great Granary               | gid=39    | "مخزن الحبوب الكبير"  | Costs and total resources for level 1 : 400	500	350	100	1350	1         | Prerequisites: Main Building Level 10, Wonder Of The World Level 0
Horse Drinking Trough(Roman)| gid=41    | "بِئْر سقي الخيول"      | Costs and total resources for level 1 : 780	420	660	540	2400	5         | Prerequisites: Stable Level 20, Rally Point Level 10

---{Military}---
Rally Point                 | gid=16    | "نقطة التجمع"         | Costs and total resources for level 1 : 110	160	90	70	430	1           | Prerequisites: none
City Wall(Roman)            | gid=31    | "حائط المدينة"        | Costs and total resources for level 1 : 70	90	170	70	400	0           | Prerequisites: none
Earth Wall(Teutons)         | gid=32    | "الحائط الأرضي"        | Costs and total resources for level 1 : 120	200	0	80	400	0            | Prerequisites: none
Palisade(Gauls)             | gid=33    | "الحاجز"              | Costs and total resources for level 1 : 160	100	80	60	400	0           | Prerequisites: none
Smithy                      | gid=13    | "أفران صهر الحديد"    | Costs and total resources for level 1 : 180	250	500	160	1090	4         | Prerequisites: Main Building Level 3, Academy Level 1
Tournament Square           | gid=14    | "ساحة البطولة"        | Costs and total resources for level 1 : 1750	2250	1530	240	5770	1   | Prerequisites: Rally Point Level 15
Barracks                    | gid=19    | "الثكنة"              | Costs and total resources for level 1 : 210	140	260	120	730	4           | Prerequisites: Rally Point Level 1, Main Building Level 3
Stable                      | gid=20    | "الإسطبل"              | Costs and total resources for level 1 : 260	140	220	100	720	5          | Prerequisites: Smithy Level 3, Academy Level 5
Workshop                    | gid=21    | "المصانع الحربية"     | Costs and total resources for level 1 : 460	510	600	320	1890	3         | Prerequisites: Academy Level 10, Main Building Level 5
Academy                     | gid=22    | "الأكاديمية"           | Costs and total resources for level 1 : 220	160	90	40	510	4          | Prerequisites: Barracks Level 3, Main Building Level 3
Trapper(Gauls)              | gid=36    | "الصياد"              | Costs and total resources for level 1 : 80	120	70	90	360	4           | Prerequisites: Rally Point Level 1
Hero's Mansion              | gid=37    | "قصر الأبطال"          | Costs and total resources for level 1 : 700	670	700	240	2310	2	       | Prerequisites: Main Building Level 3, Rally Point Level 1
Hospital                    | gid=46    | "مستشفى"              | Costs and total resources for level 1 : 320	280	420	360	1380	3         | Prerequisites: Main Building Level 10, Academy Level 15
                            |           |                       |                                                                           | Prerequisites: 
