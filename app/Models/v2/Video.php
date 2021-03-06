<?php

namespace App\Models\v2;

use App\Libs\Utils;
use App\Models\BaseModel;

use App\Helper\Token;
use \DB;
use App\Services\Shopex\Erp;
use App\Services\Shopex\Sms;
use App\Helper\Header;
use Illuminate\Support\Facades\Redis;
use Log;
use App\Services\Shopex\Logistics;

class Video extends BaseModel
{
    protected $connection = 'shop';

    protected $table = 'goods';

    public $timestamps = false;

    protected $primaryKey = 'goods_id';

    protected $guarded = [];

    protected $appends = [
        'id', 'category', 'brand', 'shop', 'sku', 'default_photo', 'photos', 'name', 'price', 'current_price', 'discount', 'is_exchange', 'exchange_score', 'sales_count', 'score', 'good_stock',
        'comment_count', 'is_liked', 'review_rate', 'intro_url', 'share_url', 'created_at', 'updated_at', 'promos', 'goods_grade', 'aspect_ratio', 'breadcrumb'
    ];

    protected $visible = [
        'id', 'category', 'brand', 'shop', 'tags', 'default_photo', 'photos', 'sku', 'name', 'price', 'is_exchange', 'exchange_score', 'current_price', 'discount', 'is_shipping', 'promos',
        'stock', 'properties','propertie_info', 'sales_count', 'attachments', 'goods_desc', 'score', 'comments', 'good_stock', 'comment_count', 'is_liked', 'review_rate', 'intro_url', 'share_url',
        'created_at', 'updated_at','is_real','is_on_sale','is_alone_sale','goods_number','market_price','integral','goods_name','goods_sn','extension_code', 'is_outer_vide_ourl', 'video_url', 'pub_id',  'goods_brief',
        'goods_grade', 'actors', 'aspect_ratio', 'breadcrumb'
    ];

    // protected $with = [];

    const NOSORT  = 0;
    const PRICE   = 1;
    const POPULAR = 2;
    const CREDIT  = 3;
    const SALE    = 4;
    const DATE    = 5;

    const ASC  = 1;
    const DESC = 2;

    public function getIdAttribute()
    {
        return $this->goods_id;
    }

    public function getCategoryAttribute()
    {
        return $this->cat_id;
    }

    public function getGoodsDescAttribute()
    {
        $pattern = '/(https?|ftp|mms)?:\/\/([A-z0-9]+[_\-]?[A-z0-9]+\.)*[A-z0-9]+\-?[A-z0-9]+\.[A-z]{2,}(\/.*)*\/?(\/images\/upload\/)/';
        if (!preg_match($pattern, $this->attributes['goods_desc'])) {
            return str_replace('/images/upload', config('app.shop_url') . '/images/upload', $this->attributes['goods_desc']);
        }
        return null;
    }

    public function getScoreAttribute()
    {
        $scale = ShopConfig::findByCode('integral_scale');
        if ($scale > 0) {
            return $this->integral / ($scale / 100);
        }
        return 0;
    }

    public function getBrandAttribute()
    {
        return $this->brand_id;
    }

    public function getShopAttribute()
    {
        $data = [];
        // $data['name'] = ShopConfig::findByCode('shop_name');
        $data['id'] = 1;
        return $data['id'];
    }

    public function getAspectRatioAttribute()
    {
        $result = (float)$this->attributes['aspect_ratio'];
        if($result == 0){
            $result = 16/9;
        }
        return $result;
    }

    public function getPromosAttribute()
    {
        $user_agent = Header::getUserAgent();
//        Log::debug("平台记录".json_encode($user_agent));
        if ($user_agent == array('Platform' => 'Wechat')) {
            $data = array();
            return $data;
        }

        return FavourableActivity::getPromoByGoods($this->goods_id, $this->category, $this->brand, true);
    }

    public function getExchangeScoreAttribute()
    {
        $exchangegoodsobj = ExchangeGoods::where('goods_id', $this->attributes['goods_id'])
            ->where('is_exchange', 1)
            ->first();
        if ($exchangegoodsobj) {
            return $exchangegoodsobj->exchange_integral;
        }
        return 0;
    }

    public function getIsExchangeAttribute()
    {
        $exchangegoodsobj = ExchangeGoods::where('goods_id', $this->attributes['goods_id'])->first();
        return !empty($exchangegoodsobj->is_exchange);
    }

    public function getIsOuterVideOurlAttribute()
    {
        return $this->is_outer_vider_url;
    }

    public function getVideourlAttribute()
    {
        $video_url = $this->video_url;
        if($video_url == null) {
            return '';
        }else{
            if (!preg_match('/^http/', $video_url)  &&!preg_match('/^https/', $video_url)) {
                $video_url = config('app.video_resource_url').'/'.$video_url;
            }
            return $video_url;
        }
    }

    public function getGoodsbriefAttribute()
    {
        return $this->goods_brief;
    }

    public function getSkuAttribute()
    {
        return $this->goods_sn;
    }

    public function getNameAttribute()
    {
        return $this->goods_name;
    }

    public function getGoodstockAttribute()
    {
        return $this->goods_number;
    }

    public function getPriceAttribute()
    {
        return $this->market_price;
    }

    public function getCurrentpriceAttribute()
    {
        $promote_price = self::bargain_price($this->promote_price, $this->promote_start_date, $this->promote_end_date);
        if (!empty($promote_price)) {
            return self::price_format($promote_price, false);
        }

        $user_price = UserRank::getMemberRankPriceByGid($this->goods_id);

        if (!empty($user_price)) {
            return self::price_format($user_price, false);
        }

        $current_price = UserRank::getMemberRankPriceByGid($this->goods_id);

        return self::price_format($current_price, false);
    }

    public function getDiscountAttribute()
    {
        $price = self::bargain_price($this->promote_price, $this->promote_start_date, $this->promote_end_date);
        if ($price > 0) {
            return [
                "price"    => $price,                                  // 促销价格
                "start_at" => $this->promote_start_date,               // 开始时间
                "end_at"   => $this->promote_end_date,                 // 结束时间
            ];
        } else {
            return null;
        }
    }

    public function getShareUrlAttribute()
    {
        $uid = Token::authorization();

        $shareUrl = config('app.shop_h5');
        $endUrl   = substr($shareUrl, -1);

        if (strcmp($endUrl, '/') != 0) {
            $shareUrl = $shareUrl . '/';
        }

        if ($uid) {
            return $shareUrl . '?u=' . $uid . '#/product?id=' . $this->goods_id;
        }

        return $shareUrl . '#/product?id=' . $this->goods_id;
    }

    public function getIslikedAttribute()
    {
        return CollectGoods::getIsLiked($this->goods_id) ? 1 : 0;
    }

    public function getGoodsGradeAttribute(){
        $goodsGrade = Comment::getGradeByGoodsId($this->goods_id);
        if($goodsGrade == null){
            return 0;
        }else{
            return $goodsGrade;
        }
    }

    public function getSalescountAttribute()
    {
        return OrderGoods::getSalesCountById($this->goods_id) + $this->virtual_sales;
    }

    public function getCommentcountAttribute()
    {
        return Comment::getCommentCountById($this->goods_id);
    }

    public function getPhotosAttribute()
    {
        //        $goods =  Goods::where('goods_id', $this->goods_id)->first();
//
//        $goods_images = formatPhoto($goods->goods_img, $goods->goods_thumb);
//
//        $arr = GoodsGallery::getPhotosById($this->goods_id);
//
//        if (!empty($goods_images)) {
//            array_unshift($arr, $goods_imcheckWatchTimesages);
//        }
//
//        if (empty($arr)) {
//            return null;
//        }
//
//        return $arr;
        return GoodsGallery::getPhotosById($this->goods_id);
    }

    public function getDefaultPhotoAttribute()
    {
        return formatPhoto2($this->goods_img, $this->goods_thumb);
    }

    public function getReviewrateAttribute()
    {
        return Comment::getCommentRateById($this->goods_id) . '%';
    }

    public function getIntrourlAttribute()
    {
        if (empty($this->goods_desc)) {
            return null;
        }
        return url('/v2/product.intro.' . $this->goods_id);
    }

    public function getBreadcrumbAttribute()
    {
        if(!empty($this->is_real) && $this->is_real == 2){
            $extend = GoodsExtendCategory::where('goods_id', $this->goods_id)->first();
            if($extend){
                $extend_cat = Category::where('cat_id', $extend['cat_id'])->first();
            }

            $cat = Category::where('cat_id', $this->cat_id)->first();

            $v1 = '';
            $v2 = '';

            if(!empty($extend_cat)){
                $v1 = $extend_cat['cat_name'];
            }

            if(!empty($cat)){
                $v2 = $cat['cat_name'];
            }

            $breadcrumb = $v1 . '/' . $v2;
            $breadcrumb = trim($breadcrumb, '/');
        }else{
            $breadcrumb = '';
        }

        return $breadcrumb;
    }

    public function getCreatedatAttribute()
    {
        return date("Y-m-d H:i:s", $this->add_time);
    }

    public function getUpdatedatAttribute()
    {
        return $this->last_update;
    }

    public static function findAll(array $attributes)
    {
        extract($attributes);


        $model = self::where(['is_delete' => 0]);

        $total = $model->count();

        $data = $model
            ->orderBy('sort_order', 'ASC')->orderBy('goods_id', 'DESC')
            ->paginate($per_page)->toArray();

        return self::formatBody(['products' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    /**
     * 首页商品列表
     */
    public static function getHomeList(array $attributes)
    {
        extract($attributes);
        $category_id = isset($category_id) ? $category_id : '';

        $key = 'video:home:list';

        if(Redis::exists($key)){
            $data = json_decode(Redis::get($key), true);
        }else{
            $hot_products = self::getRecommendGoods('is_hot', $category_id);
            $recently_products = self::getRecommendGoods('is_new', $category_id);
            $best_products = self::getRecommendGoods('is_best', $category_id);
            $data = [
                'hot_products'      => count($hot_products) == 0 ? null : $hot_products,
                'recently_products' => count($recently_products) == 0 ? null : $recently_products,
                'best_products'     => count($best_products) == 0 ? null : $best_products,
            ];
            Redis::set($key, json_encode($data));
        }

        return self::formatBody($data);
    }

    public static function getRecommendGoods($type, $category_id = '')
    {
        $model = self::where(['is_delete' => 0, 'is_on_sale' => 1, 'is_alone_sale' => 1, 'is_real' => 2]);

        if ($category_id && env('REFRESH_ECSHOP36_DATABASE')) {
            $model = $model->where('cat_id', $category_id);
        }

        $model = $model->where($type, 1);

        $limit = 12;

        // 获取数据总条数
        $count = $model->count();

        if($count == 0){
            $data = array();
        }else{
            $model = $model->with(['properties']);
            if($count <= $limit){
                $model = $model->get();
            }else{
                // 随机查询
                $offset = random_int(0, $count - $limit);
                $model = $model->offset($offset)->limit($limit)->get();
            }

            $data = $model->toArray();
        }

        return $data;
    }

    /**
     * 商品列表
     *
     * @param  array $attributes [description]
     * @return [type]             [description]
     */
    public static function getList(array $attributes)
    {
        extract($attributes);
        $prefix = DB::connection('shop')->getTablePrefix();

        if (isset($is_real) && $is_real && empty($is_real)) {
            $is_real = '2';
        }
        $where  = ['is_delete' => 0, 'is_on_sale' => 1, 'is_real' => $is_real];

        //全站商品
//        $model = self::where($where);

        $model = self::select('goods.*');

//        if ((isset($attr_value1) && $attr_value1) || (isset($attr_value2) && $attr_value2)) {
//            $model = $model->leftjoin('goods_video_attr', 'goods_video_attr.goods_id', '=', 'goods.goods_id');
//            if(isset($attr_value1) && $attr_value1){
//                $model = $model->Where('goods_video_attr.attr_value1' , '=', $attr_value1);
//            }
//            if(isset($attr_value2) && $attr_value2){
//                $model = $model->Where('goods_video_attr.attr_value2' , '=', $attr_value2);
//            }
//        }

        $model = $model->where($where);

        if (isset($is_exchange) && $is_exchange) {
            $exchange_model = ExchangeGoods::where('is_exchange', 1);

            if (isset($is_hot) && $is_hot) {
                $exchange_model->where('is_hot', $is_hot);
            }

            $exchange_goods_ids = $exchange_model->lists('goods_id');


            if ($exchange_goods_ids) {
                $model->whereIn('goods.goods_id', $exchange_goods_ids->toArray());
            }
        }

        if (isset($add_time) && $add_time) {
            $model->where(DB::raw("from_unixtime(".$prefix."goods.add_time, '%Y-%m-%d')"), $add_time);
        }

        if (isset($pub_id) && $pub_id) {
            $model->where('pub_id', $pub_id);
        }

        if (isset($is_hot) && $is_hot && empty($is_exchange)) {
            $model->where('is_hot', $is_hot);
        }

        if (isset($is_new) && $is_new) {
            $model->where('is_new', $is_new);
        }

        if (isset($is_best) && $is_best) {
            $model->where('is_best', $is_best);
        }

        if (isset($keyword) && $keyword) {
            $keyword = trim($keyword);
            $keyword = strip_tags($keyword);
            $model->where(function ($query) use ($keyword) {
                // keywords
                $query->where('goods.goods_name', 'like', '%' . strip_tags($keyword) . '%')->orWhere('keywords', strip_tags($keyword))->orWhere('goods.goods_id', strip_tags($keyword));
            });
            // 搜索历史
            Keywords::updateHistory($keyword);
        }

        if (isset($brand) && $brand) {
            $model->where('brand_id', $brand);
        }

        if ((isset($attr_value1) && $attr_value1)) {
            //获得所有扩展分类属于指定分类的所有商品ID
            $extension_goods = GoodsExtendCategory::get_extension_goods(GoodsCategory::getCategoryIds($attr_value1));
            $model->where(function ($query) use ($attr_value1, $extension_goods) {
                $query->whereIn('goods.cat_id', GoodsCategory::getCategoryIds($attr_value1))
                    ->orWhereIn('goods.goods_id', $extension_goods);
            });
        }

        if ((isset($attr_value2) && $attr_value2)) {
            $model->where('goods.cat_id', $attr_value2);
        }

        if (isset($category) && $category) {
            //获得所有扩展分类属于指定分类的所有商品ID
            $extension_goods = GoodsExtendCategory::get_extension_goods(GoodsCategory::getCategoryIds($category));
            $model->where(function ($query) use ($category, $extension_goods) {
                $query->whereIn('goods.cat_id', GoodsCategory::getCategoryIds($category))
                    ->orWhereIn('goods.goods_id', $extension_goods);
            });

//            $model->where('goods.cat_id', $category);
        }

        // 优惠活动
        if (isset($activity) && $activity) {
            if ($activity_model = FavourableActivity::where(['act_id' => $activity])->first()) {
                $range_ext = explode(',', $activity_model->act_range_ext);
                switch ($activity_model->act_range) {
                    // 指定分类
                    case FavourableActivity::FAR_CATEGORY:
                        //获得所有扩展分类属于指定分类的所有商品ID
                        $extension_goods = GoodsExtendCategory::get_extension_goods(GoodsCategory::getAllCategory($range_ext));
                        $model->where(function ($query) use ($range_ext, $extension_goods) {
                            $query->whereIn('goods.cat_id', GoodsCategory::getAllCategory($range_ext))
                                ->orWhereIn('goods.goods_id', $extension_goods);
                        });
                        break;

                    // 指定品牌
                    case FavourableActivity::FAR_BRAND:
                        $model->whereIn('brand_id', $range_ext);
                        break;

                    // 指定商品
                    case FavourableActivity::FAR_GOODS:
                        $model->whereIn('goods.goods_id', $range_ext);
                        break;
                }
            }
        }

        $total = $model->count();

        /* 图库默认按时间倒叙排序 */
        if(isset($is_real) && $is_real == 3 && !isset($sort_key)){
            $sort_key = self::DATE;
            $sort_value = '2';
        }

        if (isset($sort_key)) {
            switch ($sort_value) {
                case '1':
                    $sort = 'ASC';
                    break;

                case '2':
                    $sort = 'DESC';
                    break;

                default:
                    $sort = 'DESC';
                    break;
            }

            switch ($sort_key) {

                case self::NOSORT:
                    $model->orderBy('sort_order', $sort);
                    break;

                case self::PRICE:
                    $model->orderBy('shop_price', $sort);
                    break;

                case self::POPULAR:
                    $model->orderBy('click_count', $sort);
                    break;

                case self::CREDIT:
                    // 按照评论数
                    $model->select('*', DB::raw($prefix . 'goods.goods_id, concat( sum(comment_rank)/(count(id_value) * 5)) AS goods_rank_rate'))
                        ->leftJoin('comment', 'goods.goods_id', '=', 'comment.id_value')
                        ->groupBy('goods.goods_id')
                        ->orderBy('goods_rank_rate', $sort);

                    $total = count($model->get()->toArray());
                    break;

                case self::SALE:
                    $model->select('goods.*', DB::raw('sum(' . $prefix . 'order_goods.goods_number)+virtual_sales AS total_sales'))
                        ->leftJoin('order_goods', 'goods.goods_id', '=', 'order_goods.goods_id')
                        ->groupBy('goods.goods_id')
                        ->orderBy('total_sales', $sort);
                    $total = count($model->get()->toArray());
                    break;

                case self::DATE:
                    $model->orderBy('add_time', $sort);
                    break;

                default:
                    $model->orderBy('sort_order', 'DESC');
                    break;
            }
        } else {
            $model->orderBy('sort_order', 'DESC');
        }
        $model->orderBy('goods_id', 'DESC');
        $model->with(['properties', 'propertie_info']);

        if (isset($per_page)) {
            $data = $model->paginate($per_page)->toArray();
            return self::formatBody(['products' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
        } else {
            $data = $model->get()->toArray();
            return self::formatBody(['products' => $data]);
        }
    }

    /**
     * 智能推荐列表
     *
     * @return [type]             [description]
     */
    public static function getAiRecommendList()
    {
        $where  = ['is_delete' => 0, 'is_on_sale' => 1, 'is_real' => '2'];

        $model = self::select('*');

        $model = $model->where($where);

        $total = $model->count(); // 总数据量
        $count = 12; // 需要获取的数据量

        if ($total > $count) {
            $ids = array();
            $offsets = Utils::get_rand_number(0, $total - 1, $count);
            // 循环取数据
            foreach ($offsets as $offset) {
                $row = self::select('*')->where($where)->offset($offset)->limit(1)->get()->toArray();
                $id = reset($row)['id'];
                array_push($ids, $id);
            }

            $model->whereIn('goods_id', $ids);
        }

        $model->with(['properties', 'propertie_info']);

        $data = $model->get()->toArray();
        return self::formatBody(['products' => $data]);
    }

    /**
     * 推荐商品列表
     *
     * @param  array $attributes [description]
     * @return [type]             [description]
     */
    public static function getRecommendList(array $attributes)
    {
        extract($attributes);

        //全站商品
        $model = Goods::where(['is_delete' => 0, 'is_on_sale' => 1]);

        if (isset($brand) && $brand) {
            $model->where('brand', Brand::getBrandById($brand));
        }

        if (isset($category) && $category) {
            $model->where(function ($query) use ($category) {
                $query->whereIn('goods.cat_id', GoodsCategory::getCategoryIds($category));
            });
        }

        if (isset($product) && $product) {
            $model->where(function ($query) use ($product) {
                $query->whereIn('goods.goods_id', LinkGoods::getLinkGoodIds($product));
            });
        }
        $total = $model->count();

        $data = $model->orderBy('sort_order', 'DESC')->orderBy('goods_id', 'DESC')
            ->paginate($per_page)->toArray();

        return self::formatBody(['products' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    /**
     * 商品配件列表
     *
     * @param  array $attributes [description]
     * @return [type]             [description]
     */
    public static function getAccessoryList(array $attributes)
    {
        extract($attributes);

        //全站商品
        $model = Goods::where(['is_delete' => 0]);

        if (isset($product) && $product) {
            $model->where(function ($query) use ($product) {
                $query->whereIn('goods.goods_id', GoodsGroup::getAccessories($product));
            });
        }
        $total = $model->count();

        $data = $model
            ->with(['properties', 'tags', 'stock', 'attachments'])
            ->orderBy('sort_order', 'DESC')->orderBy('goods_id', 'DESC')
            ->paginate($per_page)->toArray();

        return self::formatBody(['products' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public static function getInfo(array $attributes)
    {
        extract($attributes);

        $key = 'video:info:' . $product;

        if(Redis::exists($key)){
            $product_data = json_decode(Redis::get($key), true);
        }else{
            $model = Video::where(['is_delete' => 0, 'goods_id' => $product]);

            $data = $model->with(['properties','propertie_info', 'tags', 'stock', 'attachments', 'actors'])->first();
            //保证属性排序正确
            $product_data = $data->toArray();

            if(!empty($product_data['properties']) && !empty($product_data['stock'])){
                $goods_attr = $product_data['stock'][0]['goods_attr'];
                $attr_ids = explode('|', $goods_attr);
                $attr = GoodsAttr::whereIn('goods_attr_id',$attr_ids)->get();
                $new_attr = $_attr = [];
                foreach ($attr as $key => $value) {
                    $_attr[$value['id']] = $value['attr_id'];
                }
                foreach ($attr_ids as $key => $value) {
                    $new_attr[] = $_attr[$value];
                }
                $properties = [];
                foreach ($product_data['properties'] as $key => $item) {
                    $properties[$item['id']] = $item;
                }
                foreach ($new_attr as $key => $value) {
                    $product_data['properties'][$key] = $properties[$value];
                }
            }

            //关联视频
            $row = LinkGoods::select('goods.*')
                ->from('link_goods as lg')
                ->leftJoin('goods as goods', 'goods.goods_id', '=', 'lg.link_goods_id')
                ->where('lg.goods_id', $product)
                ->get();

            $linkModel = array();
            foreach ($row AS $key => $val)
            {
                $linkModel[$key]['goods_id'] = $val['goods_id'];
                $linkModel[$key]['goods_name'] = $val['goods_name'];
                $linkModel[$key]['goods_thumb'] = formatPhoto($val['goods_thumb']);
            }

            $product_data['link_goods'] = $linkModel;

            //如果是视频
            if(!empty($product_data['is_real']) && ($product_data['is_real'] == '2')){
                //判断当前用户是否已经收藏该视频
                $uid = Token::authorization();
//            $count = CollectGoods::where('user_id', $uid)->count();
//            $product_data['is_collect'] = $count;
//            //判断发布者是否被关注
//            $num = UserAttention::where(['user_id' => $uid, 'att_user_id' => $product_data['pub_id']])->count();
//            if($num == 1){
//                $product_data['is_attention'] = 1;
//            }else{
//                $product_data['is_attention'] = 0;
//            }
                //播放数量
                $total = VideoWatchLog::where('video_id', $product)
                    ->groupby('video_id')
                    ->count();
                $product_data['play_total'] = $total;
            }

            if (!$data) {
                return self::formatError(self::NOT_FOUND);
            }

            if (!$data->is_on_sale) {
                return self::formatError(self::BAD_REQUEST, trans('message.good.off_sale'));
            }
            // $current_price = UserRank::getMemberRankPriceByGid($product);
            //$data['promos'] = FavourableActivity::getPromoByGoods($product,$data->cat_id, $data->brand_id);

//        if ($data->promote_price == 0) {
//            $current_price = UserRank::getMemberRankPriceByGid($product);
//            return self::formatBody(['product' => array_merge($data->toArray(), ['current_price' => $current_price])]);
//        }

            Redis::set($key, json_encode($product_data));
        }

        return self::formatBody(['product' => $product_data]);
    }


    public static function getPhotoList(array $attributes)
    {
        extract($attributes);

        $model = GoodsGallery::where('goods_id', $product);
        $total = $model->count();

        $data = $model->orderBy('sort_order')->paginate($per_page)->toArray();
        $photoItem = $data['data'];

        return self::formatBody(['photoItem' => $photoItem, 'paged' => self::formatPaged($page, $per_page, $total)]);
    }


    public static function getIntro($id)
    {
        if ($model = self::where('goods_id', $id)->first()) {
            $pattern = '/(https?|ftp|mms)?:\/\/([A-z0-9]+[_\-]?[A-z0-9]+\.)*[A-z0-9]+\-?[A-z0-9]+\.[A-z]{2,}(\/.*)*\/?(\/images\/upload\/)/';
            if (!preg_match($pattern, $model->goods_desc)) {
                $model->goods_desc = str_replace('/images/upload', config('app.shop_url') . '/images/upload', $model->goods_desc);
            }
            return view('goods.intro', ['goods' => $model->toArray()]);
        }
    }

    public static function getShare($id)
    {
        if ($model = self::where('goods_id', $id)->first()) {
            $reviews = Comment::with('author')->where(['comment_type' => Comment::GOODS, 'id_value' => $id])->get();
            $shop    = ShopConfig::getSiteInfo();
            return view('goods.share', ['goods' => $model->toArray(), 'reviews' => $reviews->toArray(), 'shop' => $shop]);
        }
    }

    /**
     * 取得商品最终使用价格
     *
     * @param   string  $goods_id      商品编号
     * @param   string  $goods_num     购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix     $property      规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    public static function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $property = [])
    {
        $final_price   = '0'; //商品最终购买价格
        $volume_price  = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price    = '0'; //商品会员价格

        //取得商品优惠价格列表
        $price_list = self::get_volume_price_list($goods_id, '1');
        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        $user_agent = Header::getUserAgent();
//        Log::debug("平台记录".json_encode($user_agent));
        if ($user_agent == array('Platform' => 'Wechat')) {
            $volume_price  = '0'; //商品优惠价格
        } 
        // else {
        //     $goods_list =array(
        //          array(
        //              'goods_id' => $goods_id,
        //              'price' => Goods::get_final_price($goods_id, $goods_num, true, []),
        //              'amount' => $goods_num
        //          )
        //      );
        //      $volume_price = Logistics::discount($goods_list);
        // }

        //取得商品促销价格列表
        /* 取得商品信息 */
        $goods = Goods::where('goods.goods_id', $goods_id)->leftJoin('member_price', function ($query) {
            $query->on('member_price.goods_id', '=', 'goods.goods_id');
        })->first(['goods.promote_price', 'goods.promote_start_date', 'goods.promote_end_date', 'member_price.user_price']);

        if (!$goods) {
            // 商品不存在
            return 0;
        }
        $member_price = UserRank::getMemberRankPriceByGid($goods_id);
        $user_rank    = UserRank::getUserRankByUid();
        $user_price   = MemberPrice::getMemberPriceByUid($user_rank['rank_id'], $goods_id);
        // $goods['user_price'] = $user_price;
        $goods['shop_price'] = isset($user_price) ? $user_price : $member_price;
        /* 计算商品的促销价格 */
        if ($goods->promote_price > 0) {
            $promote_price = self::bargain_price($goods->promote_price, $goods->promote_start_date, $goods->promote_end_date);
        } else {
            $promote_price = 0;
        }

        //取得商品会员价格列表
        $user_price = $goods['shop_price'];

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && empty($promote_price)) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && empty($promote_price)) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && !empty($promote_price)) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && !empty($promote_price)) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($property)) {
                $property_price = GoodsAttr::property_price($property);
                $final_price    += $property_price;
            }
        }
        //显示价格的格式更新
        $final_price = self::price_format($final_price, false);

        //返回商品最终购买价格
        return $final_price;
    }


    /**
     * 取得商品优惠价格列表
     *
     * @param   string $goods_id   商品编号
     * @param   string $price_type 价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
     *
     * @return  优惠价格列表
     */
    public static function get_volume_price_list($goods_id, $price_type = '1')
    {
        $volume_price = [];
        $temp_index   = '0';

        // $sql = "SELECT `volume_number` , `volume_price`".
        //        " FROM " .$GLOBALS['ecs']->table('volume_price'). "".
        //        " WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "'".
        //        " ORDER BY `volume_number`";

        // $res = $GLOBALS['db']->getAll($sql);

        $res = VolumePrice::where('goods_id', $goods_id)->where('price_type', $price_type)->orderBy('volume_number')->get();

        foreach ($res as $k => $v) {
            $volume_price[$temp_index]                 = [];
            $volume_price[$temp_index]['number']       = $v['volume_number'];
            $volume_price[$temp_index]['price']        = $v['volume_price'];
            $volume_price[$temp_index]['format_price'] = self::price_format($v['volume_price']);
            $temp_index++;
        }
        return $volume_price;
    }


    /**
     * 判断某个商品是否正在特价促销期
     *
     * @access  public
     * @param   float  $price 促销价格
     * @param   string $start 促销开始日期
     * @param   string $end   促销结束日期
     * @return  float   如果还在促销期则返回促销价，否则返回0
     */
    public static function bargain_price($price, $start, $end)
    {
        if ($price == 0) {
            return 0;
        } else {
            $time = time();
            // $time = gmtime();
            if ($time >= $start && $time <= $end) {
                return $price;
            } else {
                return 0;
            }
        }
    }


    /**
     * 格式化商品价格
     *
     * @access  public
     * @param   float $price 商品价格
     * @return  string
     */
    public static function price_format($price, $change_price = true)
    {
        $price_format = 1;
        if ($price === '') {
            $price = 0;
        }
        if ($change_price) {
            switch ($price_format) {
                case 0:
                    $price = number_format($price, 2, '.', '');
                    break;
                case 1: // 保留不为 0 的尾数
                    $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                    if (substr($price, -1) == '.') {
                        $price = substr($price, 0, -1);
                    }
                    break;
                case 2: // 不四舍五入，保留1位
                    $price = substr(number_format($price, 2, '.', ''), 0, -1);
                    break;
                case 3: // 直接取整
                    $price = intval($price);
                    break;
                case 4: // 四舍五入，保留 1 位
                    $price = number_format($price, 1, '.', '');
                    break;
                case 5: // 先四舍五入，不保留小数
                    $price = round($price);
                    break;
            }
        } else {
            $price = number_format($price, 2, '.', '');
        }

        // return sprintf('￥%s元', $price);
        return $price;
    }


    /**
     * 立即购买
     *
     * @param     int    $shop            // 店铺ID(无)
     * @param     int    $consignee       // 收货人ID
     * @param     int    $shipping        // 快递ID
     * @param     string $invoice_type    // 发票类型，如：公司、个人
     * @param     string $invoice_content // 发票内容，如：办公用品、礼品
     * @param     string $invoice_title   // 发票抬头，如：xx科技有限公司
     * @param     int    $coupon          // 优惠券ID (无)
     * @param     int    $cashgift        // 红包ID
     * @param     int    $comment         // 留言
     * @param     int    $score           // 积分
     * @param     int    $exchange_score  // 兑换商品所需积分
     * @param     int    $product         // 商品ID
     * @param     string $property        // 用户选择的属性ID
     * @param     int    $amount          // 数量
     */

    public static function purchase(array $attributes)
    {
        Log::info("create order log begin==".__FUNCTION__."==".__LINE__."==attributes:",$attributes);
        extract($attributes);

        $user_id = Token::authorization();
        $user_info = Member::user_info($user_id);
        //如果是积分兑换商品
        if (isset($exchange_score)) {
            $exchangegoods = ExchangeGoods::where(['goods_id' => $product, 'is_exchange' => 1])->first();
            if (!$exchangegoods) {
                return self::formatError(self::NOT_FOUND);
            }

            //如果用户积分少于兑换所需积分
            if ($user_info['pay_points'] < $exchange_score * $amount) {
                return self::formatError(self::BAD_REQUEST, trans('message.exchange.exchange_score_not_enough'));
            };
        }
        
        //获取商品信息
        $good = Goods::where(['goods_id' => $product, 'is_delete' => 0])->first();
        $good = $good->toArray();
        if (!$good) {
            // 商品不存在
            return self::formatError(self::NOT_FOUND);
        }

        /* 是否正在销售 */
        if ($good['is_on_sale'] == 0) {
            return self::formatError(self::BAD_REQUEST, trans('message.good.off_sale'));
        }

        /* 不是配件时检查是否允许单独销售 */
        if ($good['is_alone_sale'] == 0) {
            //不能单独销售
            return self::formatError(self::BAD_REQUEST, trans('message.good.not_alone'));
        }

        if (isset($property) && json_decode($property, true)) {
            $property = json_decode($property, true);
 
            if (!is_array($property)) {
                return self::formatError(self::BAD_REQUEST);
            }
        } else {
            $property = [];
        }
        $good['property'] = $property;
        Log::info("create order log==function:".__FUNCTION__."==".__LINE__."==property：",$property);
        /* 如果商品有规格则取规格商品信息 配件除外 property */
        $prod = Products::where('goods_id', $product)->first();
        $prod_arr = $prod ? $prod->toArray() : [];
        Log::info("create order log==function:".__FUNCTION__."==".__LINE__."==prod：",$prod_arr);
        if (Attribute::is_property($property) && !empty($prod)) {
            $product_info = Products::get_products_info($product, $property);
            Log::info("create order log==function:".__FUNCTION__."==".__LINE__."==is_property is not prod==product_info:",$product_info->toArray());
        }
        if (empty($product_info)) {
            Log::info("create order log==function:".__FUNCTION__."==".__LINE__."==product_info is empty");
            $product_info = json_encode(['product_number' => '', 'product_id' => 0]);
            $product_info = json_decode($product_info);
        }
        /* 检查：库存 */
        //检查：商品购买数量是否大于总库存
        if ($amount > $good['goods_number']) {
            return self::formatError(self::BAD_REQUEST, trans('message.good.out_storage'));
        }
        //商品存在规格 是货品 检查该货品库存
        if (Attribute::is_property($property) && !empty($prod)) {
            if (!empty($property)) {
                /* 取规格的货品库存 */
                if ($amount > $product_info->product_number) {
                    return self::formatError(self::BAD_REQUEST, trans('message.good.out_storage'));
                }
            }
        }

        /* 计算商品的促销价格 */
        $property_price       = GoodsAttr::property_price($property);
        $goods_price          = Goods::get_final_price($product, $amount, true, $property);
        $good['market_price'] += $property_price;
        $good['goods_number'] = $amount;
        $good['goods_price']  = $goods_price;
        $goods_attr           = Attribute::get_goods_attr_info($property);
        $goods_attr_id        = join(',', $property);
        Log::info("create order log==".__FUNCTION__."==".__LINE__."==goods_attr商品属性:".$goods_attr);
        /* 初始化要插入购物车的基本件数据 */

        //-- 完成所有订单操作，提交到数据库

        /* 取得购物类型 */
        $flow_type = Cart::CART_GENERAL_GOODS;
        Log::info("create order log==".__FUNCTION__."==".__LINE__."==flow_type=".$flow_type);
        $consignee_info = UserAddress::get_consignee($consignee);

        if (!$consignee_info) {
            return self::formatError(self::BAD_REQUEST, trans('message.consignee.not_found'));
        }

        $inv_type    = isset($invoice_type) ? $invoice_type : '';
        $inv_payee   = isset($invoice_title) ? $invoice_title : '';//发票抬头
        $inv_content = isset($invoice_content) ? $invoice_content : '';
        $postscript  = isset($comment) ? $comment : '';

        $order = [
            'shipping_id'     => intval($shipping),
            'pay_id'          => intval(0),
            'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,//包装id
            'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,//贺卡id
            'card_message'    => '',//贺卡内容
            'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
            'integral'        => isset($score) ? intval($score) : 0,//使用的积分的数量,取用户使用积分,商品可用积分,用户拥有积分中最小者
            'bonus_id'        => isset($cashgift) ? intval($cashgift) : 0,//红包ID
            // 'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
            'inv_type'        => $inv_type,
            'inv_payee'       => trim($inv_payee),
            'inv_content'     => $inv_content,
            'postscript'      => trim($postscript),
            'how_oos'         => '',//缺货处理
            // 'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
            // 'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
            'user_id'         => $user_id,
            'add_time'        => time(),
            'order_status'    => Order::OS_UNCONFIRMED,
            'shipping_status' => Order::SS_UNSHIPPED,
            'pay_status'      => Order::PS_UNPAYED,
            'agency_id'       => 0,//办事处的id
        ];

        /* 扩展信息 */
        $order['extension_code'] = '';
        $order['extension_id']   = 0;
        /* 检查积分余额是否合法 */
        if ($user_id > 0) {
            $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
            if ($order['surplus'] < 0) {
                $order['surplus'] = 0;
            }

            // 查询用户有多少积分
            $scale          = ShopConfig::findByCode('integral_scale');
            //$total_integral = $good['interal'] * $amount;
            $total_integral = $good['integral'] * $amount;

            if ($total_integral && $scale > 0) {
                $flow_points = $total_integral / ($scale / 100);
            } else {
                $flow_points = 0;
            }

            $user_points = $user_info['pay_points']; // 用户的积分总数

            $order['integral'] = min($order['integral'], $user_points, $flow_points);

            if ($order['integral'] < 0) {
                $order['integral'] = 0;
            }
        } else {
            $order['surplus']  = 0;
            $order['integral'] = 0;
        }
        /* 检查红包是否存在 */
        if ($order['bonus_id'] > 0) {
            $bonus = BonusType::bonus_info($order['bonus_id']);

            if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > $goods_price * $amount) {
                $order['bonus_id'] = 0;
            }
        }

        /* 订单中的商品 */

        /* 检查商品总额是否达到最低限购金额 */
        if ($flow_type == Cart::CART_GENERAL_GOODS && $goods_price < ShopConfig::findByCode('min_goods_amount')) { // Cart::cart_amount(true, Cart::CART_GENERAL_GOODS)
            return self::formatError(self::BAD_REQUEST, trans('message.good.min_goods_amount'));
        }
        /* 收货人信息 */
        $order['consignee'] = $consignee_info->consignee;
        $order['mobile']    = $consignee_info->mobile;
        $order['email']     = $consignee_info->email;
        $order['tel']       = $consignee_info->tel;
        $order['zipcode']   = $consignee_info->zipcode;
        $order['district']  = $consignee_info->district;
        $order['address']   = $consignee_info->address;
        $order['country']   = $consignee_info->country;
        $order['province']  = $consignee_info->province;
        $order['city']      = $consignee_info->city;

        /* 判断是不是实体商品 */
        /* 统计实体商品的个数 */
        if ($good['is_real']) {
            $is_real_good = 1;
        }

        if (isset($is_real_good)) {
            $shipping_is_real = Shipping::where('shipping_id', $order['shipping_id'])->where('enabled', 1)->first();
            if (!$shipping_is_real) {
                return self::formatError(self::BAD_REQUEST, '您必须选定一个配送方式');
            }
        }
        /* 订单中的总额 */
        $total = Order::purchase_fee($order, $good, $property_price, $goods_price, $amount, $consignee_info, $shipping, $consignee);
        Log::info("create order log==function:".__FUNCTION__."==".__LINE__."==计算订单中的总额total=",$total);
        if($total === false){
            return self::formatError(self::BAD_REQUEST, trans('message.shipping.error'));
        }
        /* 红包 */
        if (!empty($order['bonus_id'])) {
            $bonus          = BonusType::bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }

        $order['bonus'] = isset($bonus) ? $bonus['type_money'] : '';
        
        $order['goods_amount'] = $total['goods_price'];
        $order['discount']     = $total['discount'];
        $order['surplus']      = $total['surplus'];
        $order['tax']          = $total['tax'];

        // 购物车中的商品能享受红包支付的总额
        $discount_amout = $total['discount_formated'];

        // 红包和积分最多能支付的金额为商品总额
        $temp_amout = $order['goods_amount'] - $discount_amout;

        if ($temp_amout <= 0) {
            $order['bonus_id'] = 0;
        }

        /* 配送方式 */
        if ($order['shipping_id'] > 0) {
            $shipping               = Shipping::where('shipping_id', $order['shipping_id'])
                ->where('enabled', 1)
                ->first();
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
        }

        $order['shipping_fee'] = $total['shipping_fee'];
        $order['insure_fee']   = 0;

        /* 支付方式 */
        if ($order['pay_id'] > 0) {
            $payment           = payment_info($order['pay_id']);
            $order['pay_name'] = addslashes($payment['pay_name']);
        }
        $order['pay_fee'] = $total['pay_fee'];
        $order['cod_fee'] = $total['cod_fee'];

        /* 商品包装 */

        /* 祝福贺卡 */

        $order['order_amount'] = number_format($total['amount'], 2, '.', '');

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        /* 如果是积分兑换 并且运费，修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0 || (isset($exchange_score) && $order['shipping_fee'] <= 0)) {
            $order['order_status'] = Order::OS_CONFIRMED;
            $order['confirm_time'] = time();
            $order['pay_status']   = Order::PS_PAYED;
            $order['pay_time']     = time();
            $order['order_amount'] = 0;
        }

        if (isset($exchange_score)) {
            $order['goods_amount'] = 0.00;
            $order['order_amount'] = number_format($total['shipping_fee'], 2, '.', '');
            $order['integral'] = $exchange_score;
            $order['extension_code'] = 'exchange_goods';
            $order['extension_id'] = $product;
        } else {
            $order['integral'] = $total['integral'];
        }

        $order['integral_money'] = $total['integral_money'];
        // 推荐
        $affiliate = AffiliateLog::getAffiliateConfig();
        if($affiliate['on'] == 1 && $affiliate['config']['separate_by'] == AffiliateLog::ORDER){
            $order['parent_id']  = isset($invite_code) ? $invite_code : 0;
        }else{
            $order['parent_id'] = 0;
        }
        $order['order_sn']  = Order::get_order_sn(); //获取新订单号
        /* 插入订单表 */
        $order['lastmodify'] = time();
        unset($order['timestamps']);
        unset($order['perPage']);
        unset($order['incrementing']);
        unset($order['dateFormat']);
        unset($order['morphClass']);
        unset($order['exists']);
        unset($order['wasRecentlyCreated']);
        unset($order['cod_fee']);

        Log::info("create order log==function:".__FUNCTION__."==".__LINE__."==创建order之前，order:",$order);
        $new_order_id      = Order::insertGetId($order);
        $order['order_id'] = $new_order_id;

        /* 计算商品的促销价格 */
        $property_price       = GoodsAttr::property_price($property);
        $goods_price          = Goods::get_final_price($product, $amount, true, $property);
        $good['market_price'] += $property_price;
        $goods_attr           = Attribute::get_goods_attr_info($property);
        $goods_attr_id        = join(',', $property);

        /* 插入订单商品 */

        $order_good           = new OrderGoods;
        $order_good->order_id = $new_order_id;

        $order_good->goods_id       = $product;
        $order_good->goods_name     = $good['goods_name'];
        $order_good->goods_sn       = $good['goods_sn'];
        $order_good->product_id     = $product_info->product_id;
        $order_good->goods_number   = $amount;
        $order_good->market_price   = $good['market_price'];
        $order_good->goods_price    = isset($exchange_score) ? 0.00 : $goods_price;
        $order_good->goods_attr     = $goods_attr;
        $order_good->is_real        = $good['is_real'];
        $order_good->extension_code = $good['extension_code'];
        $order_good->parent_id      = 0;
        $order_good->is_gift        = 0;
        $order_good->goods_attr_id  = $goods_attr_id;
        Log::info("create order log end==".__FUNCTION__."==".__LINE__."==OrderGoods:",['order_id'=>$order_good->order_id,'goods_id'=>$order_good->goods_id,'goods_name'=>$order_good->goods_name,'goods_sn'=>$order_good->goods_sn,'product_id'=>$order_good->product_id,'goods_number'=>$order_good->goods_number,'goods_attr'=>$order_good->goods_attr]);
        $order_good->save();

        /* 修改拍卖活动状态 */

        /* 处理余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['integral'] > 0) {
            AccountLog::logAccountChange(0, 0, 0, $order['integral'] * (-1), trans('message.score.pay'), $order['order_sn']);
        }


        if ($order['bonus_id'] > 0 && $temp_amout > 0 && !isset($exchange_score)) {
            UserBonus::useBonus($order['bonus_id'], $new_order_id);
        }

        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (ShopConfig::findByCode('use_storage') == '1' && ShopConfig::findByCode('stock_dec_time') == Cart::SDT_PLACE) {
            Order::change_order_goods_storage($order['order_id'], true, Cart::SDT_PLACE);
        }

        /* 给商家发邮件 */
        /* 增加是否给客服发送邮件选项 */
        /* 如果需要，发短信 */
        /* 如果订单金额为0 处理虚拟卡 */

        /* 插入支付日志 */
        // $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);


        if (!empty($order['shipping_name'])) {
            $order['shipping_name'] = trim(stripcslashes($order['shipping_name']));
        }
        $orderObj = Order::find($new_order_id);

        Erp::order($orderObj->order_sn, 'order_create');

        //发送短信
        $params = [
            'pay_status' => $order['pay_status'],//支付状态
            'consignee' => $order['consignee'],//收货人姓名
            'tel' => $order['tel'],//收货人手机号
        ];
        Sms::sendSms('sms_order_placed',$params,null);
        return self::formatBody(['order' => $orderObj]);
    }

    public static function addWatch(array $attributes){
        extract($attributes);

        $uid = Token::authorization();
        $num = VideoWatchLog::where(['user_id' => $uid, 'video_id' => $video_id])->count();

        //因为有网站和手机 所以可能$num大于1
        if ($num == 0) {
            $model = new VideoWatchLog;
            $model->user_id             = $uid;
            $model->video_id            = $video_id;
            $model->add_time            = time();

            if (!$model->save()) {
                return self::formatError(self::UNKNOWN_ERROR);
            }
        } elseif ($num >0) {
            VideoWatchLog::where(['user_id' => $uid, 'video_id' => $video_id])->update([
                'add_time' => time()
            ]);
        }

        // 获取当天观看次数
        $watchedTimes = self::getTodayWatchedTimes($uid);

        return self::formatBody(['watched_times' => $watchedTimes]);
    }


    // 获取当天观看次数
    public static function getTodayWatchedTimes($userId){
        //当天观看次数
        $prefix = DB::connection('shop')->getTablePrefix();
        $result = DB::select("select count(1) as num from ".$prefix."video_watch_log where user_id = ".$userId." and date_format(from_unixtime(add_time),'%Y-%m-%d') = date_format(now(),'%Y-%m-%d')");
        $num = $result[0]->num;
        return $num;
    }


    // 获取商品VIP时长（根据商品ID）
    public static function getGoodsVipTime($goods_id){
        $goods = self::where('goods_id', $goods_id)->first();

        return self::getGoodsVipTimeByInfo($goods);
    }

    // 获取商品VIP时长（根据商品信息）
    public static function getGoodsVipTimeByInfo($goods){
        $time = 0;
        $day = $goods['seller_note'];
        if(is_numeric($day)){
            $time = $day * 24 * 60 * 60;
        }
        return $time;
    }

    public function actors()
    {
        return $this->belongsToMany('App\Models\v2\Actors', 'goods_actor','goods_id', 'actor_id');
    }
    
    public function tags()
    {
        return $this->hasMany('App\Models\v2\Tags', 'goods_id', 'goods_id');
    }

    // public function promos()
    // {
    //     return $this->hasMany('App\Models\v2\GoodsActivity', 'goods_id', 'goods_id');

    // }

    public function properties()
    {
        return $this->belongsToMany('App\Models\v2\Attribute', 'goods_attr', 'goods_id', 'attr_id')->where('attribute.attr_type', '!=', 0)->groupBy('attr_id');
    }

    public function propertie_info()
    {
        return $this->belongsToMany('App\Models\v2\Attribute', 'goods_attr', 'goods_id', 'attr_id')->groupBy('attr_id');
    }

    public function propertie_by_attrId()
    {
        return $this->belongsToMany('App\Models\v2\Attribute', 'goods_attr', 'goods_id', 'attr_id')
            ->where('goods_attr.attr_value', "大陆")
            ->groupBy('attr_id');
    }

    public function attachments()
    {
        return $this->hasMany('App\Models\v2\GoodsGroup', 'parent_id', 'goods_id');
    }

    public function stock()
    {
        return $this->hasMany('App\Models\v2\Products', 'goods_id', 'goods_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\v2\Comment', 'id_value', 'goods_id')->where('comment.comment_type', 0)->where('comment_rank', '>', 3); //商品
    }
    
    public function linkGoods()
    {
        return $this->belongsToMany('App\Models\v2\LinkGoods', 'link_goods');
    }
}