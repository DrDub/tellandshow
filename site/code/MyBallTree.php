<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';

use Rubix\ML\Graph\Nodes\Ball;
use Rubix\ML\Graph\Nodes\Clique;
use Rubix\ML\Graph\Trees\BallTree;

class MyBallTree extends BallTree {

    public array $itemdata; // cluster, is_centroid, centerIdx
    public array $centers;  // instances of Clique
    public array $centerDistances;  // array of array of float, triangular matrix

    public function __construct(array $itemdata, int $maxLeafSize = 30, ?Distance $kernel = null){
        parent::__construct($maxLeafSize, $kernel);
        $this->itemdata = $itemdata;
    }

    public function itemCount($current) : int {
        if($current instanceof Ball) {
            return $this->itemCount($current->left()) + $this->itemCount($current->right());
        }
        return $current->dataset()->numSamples();
    }
    
    public function dump($current = null, $indent="") : void {
        $current = $current ?? $this->root;

        if($current instanceof Ball) {
            echo $indent."Ball[".strlen($indent)."] radius=" . $current->radius() ." size=" . $this->itemCount($current)."\n";
            echo $indent."Left:\n";
            $this->dump($current->left(), "$indent  ");
            echo $indent."Right:\n";
            $this->dump($current->right(), "$indent  ");
        }else{ // must be a clique
            echo $indent."Clique[".strlen($indent)."] radius=" . $current->radius() ." size=" . $current->dataset()->numSamples()."\n";
        }
    }

    public function allCenters(array &$accum, $current = null) : void {
        $current = $current ?? $this->root;

        if($current instanceof Ball) {
            $this->allCenters($accum, $current->left() );
            $this->allCenters($accum, $current->right());
        }else{ // must be a clique
            if($current->center()) {
                $accum[] = $current; 
            }
        }
    }

    public function indexAndComputeDistances() : void {
        $this->centers = [];
        $this->allCenters($this->centers);
        $actualCenters = [];
        foreach($this->centers as $centerIdx => $center) {
            $items = $center->dataset()->labels();
            foreach($items as $item) {
                $this->itemdata[$item][] = $centerIdx;
            }
        }
        $size = count($this->centers);
        $this->centerDistances = [];
        foreach($this->centers as $idx => $center) {
            if($idx < $size - 1) {
                $row = [];
                foreach(range($idx+1, $size-1) as $idx2) {
                    $row[] = $this->kernel->compute($center->center(), $this->centers[$idx2]->center());
                }
                $this->centerDistances[] = $row;
            }
        }
    }

    public function centerDistance(int $x, int $y) : float {
        if($x == $y) {
            return 0.0;
        }
        if($x > $y) {
            [ $y, $x ] = [ $x, $y ];
        }
        return $this->centerDistances[$x][$y-$x-1];
    }

    public function fetchItemVector(Clique $center, int $item) : array {
        foreach($center->dataset()->labels() as $idx => $cl_item) {
            if($cl_item == $item) {
                return $center->dataset()->sample($idx);
            }
        }
        return null;
    }

    public function getRemainingCentersAndItems(array $annotated) : array {
        $available_centers = range(0, count($this->centers) - 1);
        $fully_available_centers = $available_centers;
        $centerIdxToRemainingItems = array();
        foreach($this->centers as $center) {
            $setItems = array();
            foreach($center->dataset()->labels() as $item){
                $setItems[$item] = 1;
            }
            $centerIdxToRemainingItems[] = $setItems;
        }
        
        foreach($annotated as $item => $none) {
            $centerIdx = $this->itemdata[$item][2];
            unset($fully_available_centers[$centerIdx]);
            unset($centerIdxToRemainingItems[$centerIdx][$item]);
            if(! $centerIdxToRemainingItems[$centerIdx]) {
                unset($available_centers[$centerIdx]);
            }
        }
        return [ $fully_available_centers, $available_centers, $centerIdxToRemainingItems ];
    }
}
