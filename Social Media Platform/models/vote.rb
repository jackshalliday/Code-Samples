class Vote
  include Neo4j::ActiveRel
  include Neo4j::Timestamps

  UP_SCORE    =  1
  DOWN_SCORE  = -1

  creates_unique

  from_class :User
  to_class :Post
  type :voted_on

  property :score, type: Integer
  validates :score, presence: true, inclusion: {in: [UP_SCORE, DOWN_SCORE]}
end